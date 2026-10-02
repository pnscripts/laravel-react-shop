<?php

namespace PnShop\Payment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use PnShop\Payment\Models\Refund;

/**
 * Dispatched after money was returned to the customer and the order updated.
 */
final class RefundCompleted
{
    use Dispatchable;

    public function __construct(public readonly Refund $refund) {}
}
