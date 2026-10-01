<?php

namespace PnShop\Plugins\Stripe;

use PnShop\Extension\Plugin;
use PnShop\Payment\PaymentGatewayManager;

class StripePlugin extends Plugin
{
    protected function bootPlugin(): void
    {
        $this->app->make(PaymentGatewayManager::class)->register(StripeGateway::class);
    }
}
