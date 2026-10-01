<?php

namespace Tests\Feature\Shipping;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Shipping\Carriers\FlatRate;
use PnShop\Shipping\Contracts\ShippingCarrier;
use PnShop\Shipping\Testing\ShippingCarrierContractTests;
use Tests\TestCase;

class FlatRateContractTest extends TestCase
{
    use RefreshDatabase, ShippingCarrierContractTests;

    protected function carrier(): ShippingCarrier
    {
        return new FlatRate;
    }
}
