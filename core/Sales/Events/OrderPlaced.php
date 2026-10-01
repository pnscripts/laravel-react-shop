<?php

namespace PnShop\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use PnShop\Sales\Models\Order;

/**
 * Dispatched after an order has been committed to the database.
 */
final class OrderPlaced
{
    use Dispatchable;

    public function __construct(public readonly Order $order) {}
}
