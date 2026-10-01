<?php

namespace Tests\Feature\Shipping;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Shipping\Carriers\PriceBased;
use PnShop\Shipping\Contracts\ShippingCarrier;
use PnShop\Shipping\Testing\ShippingCarrierContractTests;
use Tests\TestCase;

class PriceBasedContractTest extends TestCase
{
    use RefreshDatabase, ShippingCarrierContractTests;

    protected function carrier(): ShippingCarrier
    {
        return new PriceBased;
    }
}
