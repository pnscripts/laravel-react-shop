<?php

namespace Tests\Feature\Payment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Payment\Contracts\PaymentGateway;
use PnShop\Payment\Gateways\CashOnDelivery;
use PnShop\Payment\Testing\PaymentGatewayContractTests;
use Tests\TestCase;

class CashOnDeliveryContractTest extends TestCase
{
    use PaymentGatewayContractTests, RefreshDatabase;

    protected function gateway(): PaymentGateway
    {
        return new CashOnDelivery;
    }
}
