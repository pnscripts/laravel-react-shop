<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderStatusService
{
    public const CANCELLED = 'cancelled';

    /**
     * Move an order to a new status, returning stock on cancel and taking it again on un-cancel.
     *
     * @throws CheckoutException when un-cancelling an order whose stock is no longer available.
     */
    public function change(Order $order, OrderStatus $status): void
    {
        DB::transaction(function () use ($order, $status) {
            $order = Order::query()->with(['orderStatus', 'items'])->lockForUpdate()->findOrFail($order->id);

            $wasCancelled = $order->orderStatus?->name === self::CANCELLED;
            $isCancelled = $status->name === self::CANCELLED;

            $items = $order->items->whereNotNull('product_id')->sortBy('product_id');

            if (! $wasCancelled && $isCancelled) {
                foreach ($items as $item) {
                    Product::withTrashed()->whereKey($item->product_id)->increment('stock', $item->quantity);
                }
            }

            if ($wasCancelled && ! $isCancelled) {
                foreach ($items as $item) {
                    $taken = Product::query()
                        ->whereKey($item->product_id)
                        ->where('stock', '>=', $item->quantity)
                        ->decrement('stock', $item->quantity);

                    if ($taken === 0) {
                        throw new CheckoutException("Not enough stock to reopen this order ({$item->product_title}).");
                    }
                }
            }

            $order->update(['order_status_id' => $status->id]);
        });
    }
}
