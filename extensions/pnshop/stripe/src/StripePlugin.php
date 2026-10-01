<?php

namespace PnShop\Plugins\Stripe;

use Illuminate\Support\Facades\Event;
use PnShop\Extension\Plugin;
use PnShop\Payment\PaymentGatewayManager;
use PnShop\Sales\Events\OrderStateChanged;
use PnShop\Sales\States\OrderStatus;

class StripePlugin extends Plugin
{
    protected function bootPlugin(): void
    {
        $this->app->make(PaymentGatewayManager::class)->register(StripeGateway::class);

        // A cancelled order's Checkout Session is expired, so it can no longer be paid.
        Event::listen(OrderStateChanged::class, function (OrderStateChanged $event): void {
            if ($event->to !== OrderStatus::Cancelled) {
                return;
            }

            foreach ($event->order->payments()->where('gateway', 'stripe')->get() as $payment) {
                if (is_string($payment->reference) && str_starts_with($payment->reference, 'cs_')) {
                    try {
                        $this->app->make(StripeClient::class)->post('checkout/sessions/'.$payment->reference.'/expire', []);
                    } catch (StripeException $e) {
                        // Already completed or expired: a late payment is flagged when it is confirmed.
                        report($e);
                    }
                }
            }
        });
    }
}
