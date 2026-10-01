<?php

namespace PnShop\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use PnShop\Sales\Models\Order;
use PnShop\Sales\States\OrderState;

/**
 * Dispatched after one of an order's states changed (and the change was committed).
 */
final class OrderStateChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Order $order,
        public readonly OrderState $from,
        public readonly OrderState $to,
        public readonly ?string $note = null,
    ) {}
}
