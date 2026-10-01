<?php

namespace Tests\Feature\Shipping;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Shipping\Carriers\WeightBased;
use PnShop\Shipping\Contracts\ShippingCarrier;
use PnShop\Shipping\Testing\ShippingCarrierContractTests;
use Tests\TestCase;

class WeightBasedContractTest extends TestCase
{
    use RefreshDatabase, ShippingCarrierContractTests;

    protected function carrier(): ShippingCarrier
    {
        return new WeightBased;
    }
}
