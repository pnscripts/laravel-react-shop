<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use Illuminate\Support\Facades\DB;
use PnShop\Catalog\Models\ProductVariant;
use PnShop\Inventory\Exceptions\InsufficientStock;
use PnShop\Inventory\InventoryService;
use PnShop\Inventory\StockMovementReason;

class OrderStatusService
{
    public const CANCELLED = 'cancelled';

    public function __construct(private InventoryService $inventory) {}

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

            if ($wasCancelled !== $isCancelled) {
                foreach ($order->items->whereNotNull('product_variant_id')->sortBy('product_variant_id') as $item) {
                    $this->moveStock($order, $item, $isCancelled);
                }
            }

            $order->update(['order_status_id' => $status->id]);
        });
    }

    private function moveStock(Order $order, OrderItem $item, bool $returning): void
    {
        $variant = ProductVariant::withTrashed()->find($item->product_variant_id);

        if ($variant === null) {
            return;
        }

        try {
            $this->inventory->adjust(
                $variant,
                $returning ? $item->quantity : -$item->quantity,
                $returning ? StockMovementReason::OrderCancelled : StockMovementReason::OrderReopened,
                $order,
            );
        } catch (InsufficientStock) {
            throw new CheckoutException(__('Not enough stock to reopen this order (:product).', ['product' => (string) $item->product_title]));
        }
    }
}
