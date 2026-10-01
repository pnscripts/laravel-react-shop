<?php

namespace Tests\Feature\Payment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Payment\Contracts\PaymentGateway;
use PnShop\Payment\Gateways\BankTransfer;
use PnShop\Payment\Testing\PaymentGatewayContractTests;
use Tests\TestCase;

class BankTransferContractTest extends TestCase
{
    use PaymentGatewayContractTests, RefreshDatabase;

    protected function gateway(): PaymentGateway
    {
        return new BankTransfer;
    }
}
