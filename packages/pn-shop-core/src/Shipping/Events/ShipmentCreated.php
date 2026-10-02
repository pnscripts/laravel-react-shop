<?php

namespace PnShop\Shipping\Events;

use Illuminate\Foundation\Events\Dispatchable;
use PnShop\Shipping\Models\Shipment;

/**
 * Dispatched after a shipment has been recorded and the order's fulfillment updated.
 */
final class ShipmentCreated
{
    use Dispatchable;

    public function __construct(public readonly Shipment $shipment) {}
}
