<?php

namespace PnShop\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PnShop\Catalog\Models\ProductVariant;
use PnShop\Inventory\Exceptions\InsufficientStock;
use PnShop\Inventory\InventoryService;
use PnShop\Inventory\OrderStockStatus;
use PnShop\Inventory\StockMovementReason;
use PnShop\Sales\Events\OrderStateChanged;
use PnShop\Sales\Exceptions\InvalidOrderTransition;
use PnShop\Sales\Exceptions\OrderException;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Models\OrderHistory;
use PnShop\Sales\Models\OrderItem;
use PnShop\Sales\States\FulfillmentStatus;
use PnShop\Sales\States\OrderState;
use PnShop\Sales\States\OrderStatus;
use PnShop\Sales\States\PaymentStatus;

/**
 * The only way to change an order's status, payment or fulfillment state.
 *
 * Every change is checked against the state machine, recorded in the order history
 * with its actor, moves stock where needed and dispatches OrderStateChanged after commit:
 *
 * - cancelling releases reserved stock, or puts shipped stock back on the shelf;
 * - reopening a cancelled order reserves the stock again (or takes it, when it had shipped);
 * - marking the order shipped takes the reserved stock off the shelf;
 * - a payment on a pending order moves it to processing.
 */
class OrderWorkflow
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * @throws OrderException when the change is not allowed or stock is no longer available.
     */
    public function transition(Order $order, OrderState $to, ?Model $actor = null, ?string $note = null): Order
    {
        /** @var list<array{OrderState, OrderState}> $changes */
        $changes = [];

        DB::transaction(function () use ($order, $to, $actor, $note, &$changes) {
            $locked = Order::query()->with('items')->lockForUpdate()->findOrFail($order->id);

            $changes[] = $this->apply($locked, $to, $actor, $note);

            if ($to === PaymentStatus::Paid && $locked->status === OrderStatus::Pending) {
                $changes[] = $this->apply($locked, OrderStatus::Processing, $actor, null);
            }
        });

        $order->refresh();

        foreach (array_filter($changes) as [$from, $state]) {
            OrderStateChanged::dispatch($order, $from, $state, $state === $to ? $note : null, $actor);
        }

        return $order;
    }

    public function addNote(Order $order, string $note, ?Model $actor = null): OrderHistory
    {
        return $this->record($order, OrderHistory::NOTE, null, null, $note, $actor);
    }

    /**
     * The first history entry, written by checkout.
     */
    public function recordPlaced(Order $order, ?Model $actor = null): void
    {
        $this->record($order, OrderStatus::field(), null, $order->status->value, 'Order placed', $actor);
    }

    /**
     * @return array{OrderState, OrderState}|null the change made, or null when the order already was in that state
     */
    private function apply(Order $order, OrderState $to, ?Model $actor, ?string $note): ?array
    {
        $field = $to::field();
        /** @var OrderState $from */
        $from = $order->{$field};

        if ($from === $to && ! $to->canTransitionTo($to)) {
            return null;
        }

        if (! $from->canTransitionTo($to)) {
            throw InvalidOrderTransition::between($from, $to);
        }

        if ($order->status === OrderStatus::Cancelled && $to instanceof FulfillmentStatus) {
            throw InvalidOrderTransition::cancelled();
        }

        $this->moveStock($order, $this->stockStatusAfter($order, $to));

        $order->{$field} = $to;
        $order->save();

        $this->record($order, $field, $from->value, $to->value, $note, $actor);

        return [$from, $to];
    }

    private function stockStatusAfter(Order $order, OrderState $to): OrderStockStatus
    {
        return match (true) {
            $to === OrderStatus::Cancelled => OrderStockStatus::Released,
            $order->status === OrderStatus::Cancelled && $to instanceof OrderStatus => $order->fulfillment_status === FulfillmentStatus::Unfulfilled
                ? OrderStockStatus::Reserved
                : OrderStockStatus::Fulfilled,
            $to === FulfillmentStatus::Fulfilled => OrderStockStatus::Fulfilled,
            default => $order->stock_status,
        };
    }

    private function moveStock(Order $order, OrderStockStatus $target): void
    {
        if ($target === $order->stock_status) {
            return;
        }

        foreach ($order->items->whereNotNull('product_variant_id')->sortBy('product_variant_id') as $item) {
            $this->moveItemStock($order, $item, $order->stock_status, $target);
        }

        $order->stock_status = $target;
    }

    private function moveItemStock(Order $order, OrderItem $item, OrderStockStatus $from, OrderStockStatus $to): void
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
            throw new OrderException(__('Not enough stock to reopen this order (:product).', ['product' => (string) $item->product_title]));
        }
    }

    private function record(Order $order, string $field, ?string $from, ?string $to, ?string $note, ?Model $actor): OrderHistory
    {
        return OrderHistory::query()->create([
            'order_id' => $order->id,
            'field' => $field,
            'from' => $from,
            'to' => $to,
            'note' => $note,
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor?->getKey(),
        ]);
    }
}
