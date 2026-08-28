<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(private ShoppingCartService $cart)
    {
    }

    /**
     * Create an order from the current cart, decrement stock, and clear the cart.
     *
     * @param  array{name: string, email: string, phone: string, address: string, payment_method_id: int}  $data
     *
     * @throws Exception
     */
    public function place(array $data, ?User $user = null): Order
    {
        $items = $this->cart->getCartItems();

        if ($items->isEmpty()) {
            throw new Exception('Your cart is empty.');
        }

        return DB::transaction(function () use ($data, $user, $items) {
            $status = OrderStatus::firstOrCreate(['name' => 'pending']);

            $order = Order::create([
                'user_id' => $user?->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'payment_method_id' => $data['payment_method_id'],
                'order_status_id' => $status->id,
            ]);

            foreach ($items as $item) {
                $product = Product::query()->lockForUpdate()->find($item->product_id);

                if (! $product || ! $product->is_active) {
                    throw new Exception("Product {$item->title} is no longer available.");
                }

                if ($product->stock < $item->quantity) {
                    throw new Exception("Not enough stock for {$product->title}. Available: {$product->stock}.");
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'discount_price' => $item->discount_price ?? 0,
                ]);

                $product->decrement('stock', $item->quantity);
            }

            $this->cart->clearCart();

            return $order->load(['items.product', 'orderStatus', 'paymentMethod']);
        });
    }
}
