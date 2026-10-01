<?php

namespace PnShop\Sales;

use Illuminate\Support\Facades\DB;
use PnShop\Catalog\Models\ProductVariant;
use PnShop\Inventory\Exceptions\InsufficientStock;
use PnShop\Inventory\InventoryService;
use PnShop\Inventory\OrderStockStatus;
use PnShop\Inventory\StockMovementReason;
use PnShop\Sales\Exceptions\CheckoutException;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Models\OrderItem;
use PnShop\Sales\Models\OrderStatus;

/**
 * Changes an order's status and moves its stock to match:
 *
 * - placed (any open status): units are reserved;
 * - shipped (FULFILLED statuses): reserved units leave the shelf;
 * - cancelled: reserved units are released, shipped units go back on the shelf;
 * - reopened: units are reserved again, which fails when they are gone.
 */
class OrderStatusService
{
    public const CANCELLED = 'cancelled';

    /** Statuses in which the goods have left the warehouse. */
    public const FULFILLED = ['shipped', 'delivered', 'completed'];

    public function __construct(private InventoryService $inventory) {}

    /**
     * @throws CheckoutException when reopening an order whose stock is no longer available.
     */
    public function change(Order $order, OrderStatus $status): void
    {
        DB::transaction(function () use ($order, $status) {
            $order = Order::query()->with(['items'])->lockForUpdate()->findOrFail($order->id);

            $target = match (true) {
                $status->name === self::CANCELLED => OrderStockStatus::Released,
                in_array($status->name, self::FULFILLED, true) => OrderStockStatus::Fulfilled,
                // Going back from shipped to an open status does not bring the goods back.
                $order->stock_status === OrderStockStatus::Fulfilled => OrderStockStatus::Fulfilled,
                default => OrderStockStatus::Reserved,
            };

            if ($target !== $order->stock_status) {
                foreach ($order->items->whereNotNull('product_variant_id')->sortBy('product_variant_id') as $item) {
                    $this->moveStock($order, $item, $order->stock_status, $target);
                }
            }

            $order->update(['order_status_id' => $status->id, 'stock_status' => $target]);
        });
    }

    private function moveStock(Order $order, OrderItem $item, OrderStockStatus $from, OrderStockStatus $to): void
    {
        $variant = ProductVariant::withTrashed()->find($item->product_variant_id);

        if ($variant === null) {
            return;
        }

        $quantity = $item->quantity;

        try {
            match ([$from, $to]) {
                [OrderStockStatus::Reserved, OrderStockStatus::Fulfilled] => $this->inventory->commit($variant, $quantity, StockMovementReason::OrderFulfilled, $order),
                [OrderStockStatus::Reserved, OrderStockStatus::Released] => $this->inventory->release($variant, $quantity),
                [OrderStockStatus::Fulfilled, OrderStockStatus::Released] => $this->inventory->adjust($variant, $quantity, StockMovementReason::OrderCancelled, $order),
                [OrderStockStatus::Released, OrderStockStatus::Reserved] => $this->inventory->reserve($variant, $quantity),
                [OrderStockStatus::Released, OrderStockStatus::Fulfilled] => $this->inventory->adjust($variant, -$quantity, StockMovementReason::OrderReopened, $order),
                default => null,
            };
        } catch (InsufficientStock) {
            throw new CheckoutException(__('Not enough stock to reopen this order (:product).', ['product' => (string) $item->product_title]));
        }
    }
}
