<?php

namespace PnShop\Sales\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use PnShop\Cart\Totals\CartTotals;
use PnShop\Sales\Models\Order;

/**
 * Dispatched inside the checkout transaction, after the order, its lines and totals are
 * written and before it is committed. Listeners can record what the order used (e.g.
 * promotion redemptions) or throw a CheckoutException to abort the order.
 */
final class OrderPlacing
{
    use Dispatchable;

    public function __construct(
        public readonly Order $order,
        public readonly CartTotals $totals,
        public readonly ?User $customer,
    ) {}
}
