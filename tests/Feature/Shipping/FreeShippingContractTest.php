<?php

namespace Tests\Feature\Shipping;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Shipping\Carriers\FreeShipping;
use PnShop\Shipping\Contracts\ShippingCarrier;
use PnShop\Shipping\Testing\ShippingCarrierContractTests;
use Tests\TestCase;

class FreeShippingContractTest extends TestCase
{
    use RefreshDatabase, ShippingCarrierContractTests;

    protected function carrier(): ShippingCarrier
    {
        return new FreeShipping;
    }
}
