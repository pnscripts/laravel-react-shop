<?php

namespace PnShop\Sales\Checkout;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Collection;
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
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Payment\PaymentContext;
use PnShop\Payment\PaymentService;
use PnShop\Sales\Events\OrderPlaced;
use PnShop\Sales\Exceptions\CheckoutException;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Models\OrderAddress;
use PnShop\Sales\Models\OrderItem;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\FulfillmentStatus;
use PnShop\Sales\States\OrderStatus;
use PnShop\Sales\States\PaymentStatus;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\ShippingRequest;
use PnShop\Shipping\ShippingService;
use PnShop\Tax\TaxResult;

class CheckoutService
{
    public function __construct(
        private ShoppingCartService $cart,
        private InventoryService $inventory,
        private CartCalculator $calculator,
        private OrderWorkflow $workflow,
        private PaymentService $payments,
        private ShippingService $shipping,
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

            $shippingMethod = $this->shippingMethod($data, $items, $currency, $shipping, $user);

            $totals = $this->calculator->calculate($items, $currency, [
                'shipping_address' => $shipping,
                'billing_address' => $billing,
                'shipping_method' => $shippingMethod,
                'user' => $user,
            ]);

            $method = PaymentMethod::query()->find($data['payment_method_id']);

            if ($method === null || ! $this->payments->accepts($method, new PaymentContext($totals->total(), $shipping->country_code, $user))) {
                throw new CheckoutException(__('This payment method is not available for your order. Please choose another one.'));
            }

            $tax = $totals->meta['tax'] ?? null;

            if ($tax instanceof TaxResult) {
                foreach ($order->items()->get() as $orderItem) {
                    $orderItem->update(['tax_amount' => $tax->forLine('item:'.$orderItem->product_variant_id)]);
                }
            }

            $order->update([
                'shipping_method_id' => $shippingMethod?->id,
                'shipping_method_name' => $shippingMethod?->name,
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
     * The chosen shipping method, checked against the address and the cart; null when the
     * store has no shipping methods.
     *
     * @param  array<string, mixed>  $data
     * @param  Collection<int, CartItemDTO>  $items
     *
     * @throws CheckoutException
     */
    private function shippingMethod(array $data, Collection $items, string $currency, PostalAddress $address, ?User $user): ?ShippingMethod
    {
        if (! $this->shipping->isRequired()) {
            return null;
        }

        $method = ShippingMethod::query()->find((int) ($data['shipping_method_id'] ?? 0));
        $subtotal = $items->reduce(fn (Money $total, CartItemDTO $item) => $total->plus($item->getTotalPrice()), Money::zero($currency));

        if ($method === null || $this->shipping->quote($method, new ShippingRequest($items, $subtotal, $address->country_code, $address->postcode, $user)) === null) {
            throw new CheckoutException(__('Please choose a delivery option for this address.'));
        }

        return $method;
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
