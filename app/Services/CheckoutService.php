<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PnShop\Catalog\Models\ProductVariant;
use PnShop\Inventory\Exceptions\InsufficientStock;
use PnShop\Inventory\InventoryService;
use PnShop\Inventory\StockMovementReason;
use PnShop\Localization\Localization;

class CheckoutService
{
    public function __construct(
        private ShoppingCartService $cart,
        private InventoryService $inventory,
    ) {}

    /**
     * Place an order from the current cart.
     *
     * Prices come from the variant rows, and stock is taken through the inventory ledger
     * with a conditional update, so it cannot be oversold even under concurrent checkouts.
     *
     * @param  array{name: string, email: string, phone: string, address: string, payment_method_id: int|string}  $data
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

        $order = DB::transaction(function () use ($data, $user, $lines) {
            $variants = ProductVariant::query()
                ->whereKey(array_keys($lines))
                ->with(['product', 'optionValues'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $currency = app(Localization::class)->defaultCurrency()->code;

            $order = Order::create([
                'user_id' => $user?->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'payment_method_id' => $data['payment_method_id'],
                'order_status_id' => OrderStatus::firstOrCreate(['name' => 'pending'])->id,
                'currency' => $currency,
            ]);

            foreach ($lines as $variantId => $quantity) {
                $variant = $variants->get($variantId);

                if (! $variant || ! $variant->is_active || ! $variant->product->is_active) {
                    throw new CheckoutException(__('A product in your cart is no longer available. Please review your cart.'));
                }

                try {
                    $this->inventory->adjust($variant, -$quantity, StockMovementReason::Order, $order);
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
            }

            return $order;
        }, attempts: 3);

        $this->cart->clearCart();

        return $order->load(['items', 'orderStatus', 'paymentMethod']);
    }
}
