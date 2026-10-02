<?php

namespace PnShop\Payment\Listeners;

use PnShop\Payment\PaymentService;
use PnShop\Sales\Events\OrderStateChanged;
use PnShop\Sales\States\OrderStatus;
use PnShop\Sales\States\PaymentStatus;

/**
 * Keeps payment rows in line when staff change an order by hand: marking it paid
 * settles its open payments, cancelling it cancels them.
 */
class SyncPaymentsWithOrder
{
    public function __construct(private PaymentService $payments) {}

    public function handle(OrderStateChanged $event): void
    {
        match ($event->to) {
            PaymentStatus::Paid => $this->payments->settle($event->order, $event->actor),
            OrderStatus::Cancelled => $this->payments->cancelOpen($event->order, $event->actor),
            default => null,
        };
    }
}
