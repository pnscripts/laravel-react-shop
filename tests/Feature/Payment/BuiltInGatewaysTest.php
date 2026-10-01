<?php

namespace Tests\Feature\Payment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PnShop\Payment\Contracts\PaymentGateway;
use PnShop\Payment\Gateways\BankTransfer;
use PnShop\Payment\Gateways\CashOnDelivery;
use PnShop\Payment\PaymentGatewayManager;
use Tests\TestCase;

class BuiltInGatewaysTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{class-string<PaymentGateway>}>
     */
    public static function gateways(): array
    {
        return ['cash on delivery' => [CashOnDelivery::class], 'bank transfer' => [BankTransfer::class]];
    }

    #[DataProvider('gateways')]
    public function test_built_in_gateways_are_registered(string $gateway): void
    {
        $instance = new $gateway;

        $this->assertInstanceOf($gateway, app(PaymentGatewayManager::class)->get($instance->code()));
    }
}
