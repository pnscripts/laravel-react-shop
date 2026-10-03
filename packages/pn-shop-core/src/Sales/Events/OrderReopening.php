<?php

namespace PnShop\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use PnShop\Sales\Models\Order;

/**
 * Dispatched inside the transaction that reopens a cancelled order (Cancelled → Pending),
 * with the order row locked. A listener that throws an OrderException stops the reopening,
 * for example when a coupon the order used has been used up since.
 */
final class OrderReopening
{
    use Dispatchable;

    public function __construct(public readonly Order $order) {}
}
