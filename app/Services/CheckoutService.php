<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PnShop\Catalog\Models\Product;
use PnShop\Localization\Localization;

class CheckoutService
{
    public function __construct(private ShoppingCartService $cart) {}

    /**
     * Place an order from the current cart.
     *
     * Prices and stock are read from locked product rows, never from the session.
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

        // Lock rows in a stable order so concurrent checkouts cannot deadlock.
        ksort($lines);

        $order = DB::transaction(function () use ($data, $user, $lines) {
            $products = Product::query()
                ->whereKey(array_keys($lines))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $status = OrderStatus::firstOrCreate(['name' => 'pending']);
            $currency = app(Localization::class)->defaultCurrency()->code;

            $order = Order::create([
                'user_id' => $user?->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'payment_method_id' => $data['payment_method_id'],
                'order_status_id' => $status->id,
                'currency' => $currency,
            ]);

            foreach ($lines as $productId => $quantity) {
                $product = $products->get($productId);

                if (! $product || ! $product->is_active) {
                    throw new CheckoutException(__('A product in your cart is no longer available. Please review your cart.'));
                }

                // Conditional decrement: never lets stock go below zero, even without row locks (SQLite).
                $decremented = Product::query()
                    ->whereKey($product->id)
                    ->where('stock', '>=', $quantity)
                    ->decrement('stock', $quantity);

                if ($decremented === 0) {
                    throw new CheckoutException(__('Not enough stock for :product. Available: :stock.', ['product' => $product->title, 'stock' => $product->stock]));
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_title' => $product->title,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'currency' => $currency,
                    'price' => $product->price,
                    'discount_price' => $product->discount_price,
                ]);
            }

            return $order;
        }, attempts: 3);

        $this->cart->clearCart();

        return $order->load(['items.product', 'orderStatus', 'paymentMethod']);
    }
}
