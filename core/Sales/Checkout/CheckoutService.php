<?php

namespace PnShop\Sales\Checkout;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use PnShop\Cart\CartItemDTO;
use PnShop\Cart\ShoppingCartService;
use PnShop\Cart\Totals\CartCalculator;
use PnShop\Cart\Totals\TotalLine;
use PnShop\Catalog\Models\ProductVariant;
use PnShop\Customer\Models\CustomerAddress;
use PnShop\Customer\PostalAddress;
use PnShop\Inventory\Exceptions\InsufficientStock;
use PnShop\Inventory\InventoryService;
use PnShop\Inventory\OrderStockStatus;
use PnShop\Localization\Localization;
use PnShop\Sales\Events\OrderPlaced;
use PnShop\Sales\Exceptions\CheckoutException;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Models\OrderAddress;
use PnShop\Sales\Models\OrderItem;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\FulfillmentStatus;
use PnShop\Sales\States\OrderStatus;
use PnShop\Sales\States\PaymentStatus;

class CheckoutService
{
    public function __construct(
        private ShoppingCartService $cart,
        private InventoryService $inventory,
        private CartCalculator $calculator,
        private OrderWorkflow $workflow,
    ) {}

    /**
     * Place an order from the current cart.
     *
     * Prices come from the variant rows, and stock is reserved with a conditional update,
     * so it cannot be oversold even under concurrent checkouts. It leaves the shelf when
     * the order ships (OrderWorkflow).
     *
     * The order keeps copies of the shipping and billing addresses and its totals from
     * the cart.totals pipeline, computed from the locked variant rows.
     *
     * @param  array{email: string, shipping: array<string, mixed>, billing?: array<string, mixed>, billing_same_as_shipping?: bool, save_address?: bool, payment_method_id: int|string}  $data
     *
     * @throws CheckoutException when the cart is empty or a product is unavailable.
     */
    public function place(array $data, ?User $user = null): Order
    {
        $lines = $this->cart->getLines();

        if ($lines === []) {
            throw new CheckoutException(__('Your cart is empty.'));
        }

        // Handle variants in a stable order so concurrent checkouts cannot deadlock.
        ksort($lines);

        $shipping = PostalAddress::fromArray($data['shipping']);
        $billing = ($data['billing_same_as_shipping'] ?? true) || empty($data['billing']) ? $shipping : PostalAddress::fromArray($data['billing']);

        $order = DB::transaction(function () use ($data, $user, $lines, $shipping, $billing) {
            $variants = ProductVariant::query()
                ->whereKey(array_keys($lines))
                ->with(['product.media', 'optionValues', 'stockLevels'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $currency = app(Localization::class)->defaultCurrency()->code;

            $order = Order::create([
                'user_id' => $user?->id,
                'name' => $shipping->fullName(),
                'email' => $data['email'],
                'phone' => (string) $shipping->phone,
                'payment_method_id' => $data['payment_method_id'],
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'fulfillment_status' => FulfillmentStatus::Unfulfilled,
                'currency' => $currency,
                'locale' => app()->getLocale(),
                'stock_status' => OrderStockStatus::Reserved,
            ]);

            $order->addresses()->createMany([
                ['type' => OrderAddress::SHIPPING, ...$shipping->toArray()],
                ['type' => OrderAddress::BILLING, ...$billing->toArray()],
            ]);

            $items = collect();

            foreach ($lines as $variantId => $quantity) {
                $variant = $variants->get($variantId);

                if (! $variant || ! $variant->is_active || ! $variant->product->is_active) {
                    throw new CheckoutException(__('A product in your cart is no longer available. Please review your cart.'));
                }

                try {
                    $this->inventory->reserve($variant, $quantity);
                } catch (InsufficientStock) {
                    throw new CheckoutException(__('Not enough stock for :product. Available: :stock.', [
                        'product' => $variant->product->title,
                        'stock' => (int) $variant->available(),
                    ]));
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'product_title' => $variant->product->title,
                    'product_sku' => $variant->sku,
                    'variant_label' => $variant->label() ?: null,
                    'quantity' => $quantity,
                    'currency' => $currency,
                    'price' => $variant->price,
                    'sale_price' => $variant->isOnSale() ? $variant->sale_price : null,
                ]);

                $items->push(CartItemDTO::fromVariant($variant, $quantity));
            }

            $totals = $this->calculator->calculate($items, $currency, ['shipping_address' => $shipping, 'billing_address' => $billing, 'user' => $user]);

            $order->update([
                'subtotal' => $totals->subtotal,
                'total' => $totals->total(),
                'totals' => array_map(fn (TotalLine $line) => [
                    'code' => $line->code,
                    'label' => $line->label,
                    'amount' => $line->amount->getMinorAmount()->toInt(),
                    'included' => $line->included,
                ], $totals->lines()),
            ]);

            $this->workflow->recordPlaced($order, $user);

            return $order;
        }, attempts: 3);

        OrderPlaced::dispatch($order);

        $this->cart->clearCart();

        if ($user !== null && ($data['save_address'] ?? false)) {
            $this->saveToAddressBook($user, $shipping);
        }

        return $order->load(['items', 'paymentMethod', 'addresses']);
    }

    /**
     * Keep the shipping address for next time, unless the customer already has it.
     */
    private function saveToAddressBook(User $user, PostalAddress $address): void
    {
        $saved = $user->addresses()->get();

        if ($saved->contains(fn (CustomerAddress $existing) => $existing->toPostalAddress()->toArray() === $address->toArray())) {
            return;
        }

        $first = $saved->isEmpty();

        $user->addresses()->create([
            ...$address->toArray(),
            'is_default_shipping' => $first,
            'is_default_billing' => $first,
        ]);
    }
}
