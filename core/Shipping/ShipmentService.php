<?php

namespace PnShop\Shipping;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PnShop\Catalog\Models\ProductVariant;
use PnShop\Inventory\InventoryService;
use PnShop\Inventory\OrderStockStatus;
use PnShop\Inventory\StockMovementReason;
use PnShop\Sales\Exceptions\OrderException;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Models\OrderItem;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\FulfillmentStatus;
use PnShop\Sales\States\OrderStatus;
use PnShop\Shipping\Models\Shipment;

/**
 * Records parcels: the shipped lines leave the shelf and the order becomes partially
 * shipped or shipped.
 */
class ShipmentService
{
    public function __construct(
        private InventoryService $inventory,
        private OrderWorkflow $workflow,
    ) {}

    /**
     * @param  array<int, int>  $quantities  order item id => quantity to ship; empty ships everything left
     *
     * @throws OrderException when the order is cancelled or a quantity is more than is left to ship.
     */
    public function ship(Order $order, array $quantities = [], ?string $trackingNumber = null, ?string $note = null, ?Model $actor = null): Shipment
    {
        $shipment = DB::transaction(function () use ($order, $quantities, $trackingNumber, $note, $actor) {
            $order = Order::query()->with(['items', 'shippingMethod'])->lockForUpdate()->findOrFail($order->id);

            if ($order->status === OrderStatus::Cancelled) {
                throw new OrderException(__('A cancelled order cannot be shipped.'));
            }

            $lines = $this->linesToShip($order, $quantities);

            if ($lines === []) {
                throw new OrderException(__('Nothing is left to ship on this order.'));
            }

            $method = $order->shippingMethod;
            $trackingNumber = $trackingNumber !== null && trim($trackingNumber) !== '' ? trim($trackingNumber) : null;

            $shipment = Shipment::query()->create([
                'order_id' => $order->id,
                'shipping_method_id' => $method?->id,
                'carrier_name' => $method->name ?? $order->shipping_method_name,
                'tracking_number' => $trackingNumber,
                'tracking_url' => $trackingNumber !== null && $method !== null ? $method->carrierInstance()?->trackingUrl($trackingNumber, $method) : null,
                'note' => $note,
                'shipped_at' => now(),
                'actor_type' => $actor?->getMorphClass(),
                'actor_id' => $actor?->getKey(),
            ]);

            foreach ($lines as [$item, $quantity]) {
                $shipment->lines()->create(['order_item_id' => $item->id, 'quantity' => $quantity]);

                // Orders placed before reservations already took their stock.
                if ($order->stock_status === OrderStockStatus::Reserved && $item->product_variant_id !== null) {
                    $variant = ProductVariant::withTrashed()->find($item->product_variant_id);

                    if ($variant !== null) {
                        $this->inventory->commit($variant, $quantity, StockMovementReason::OrderFulfilled, $order);
                    }
                }

                $item->increment('quantity_fulfilled', $quantity);
            }

            return $shipment;
        });

        $order->refresh()->load('items');
        $complete = $order->items->every(fn (OrderItem $item) => $item->quantityToShip() === 0);

        $note = $shipment->tracking_number === null ? null : __('Tracking number: :number', ['number' => $shipment->tracking_number]);

        $this->workflow->transition($order, $complete ? FulfillmentStatus::Fulfilled : FulfillmentStatus::PartiallyFulfilled, $actor, is_string($note) ? $note : null);

        return $shipment;
    }

    /**
     * @param  array<int, int>  $quantities
     * @return list<array{OrderItem, int}>
     */
    private function linesToShip(Order $order, array $quantities): array
    {
        $lines = [];

        foreach ($order->items as $item) {
            $left = $item->quantityToShip();
            $quantity = $quantities === [] ? $left : (int) ($quantities[$item->id] ?? 0);

            if ($quantity > $left) {
                throw new OrderException(__('Only :left of :product are left to ship.', ['left' => $left, 'product' => (string) $item->product_title]));
            }

            if ($quantity > 0) {
                $lines[] = [$item, $quantity];
            }
        }

        return $lines;
    }
}
