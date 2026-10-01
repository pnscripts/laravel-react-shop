<?php

namespace Tests\Feature\Shipping;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Shipping\Carriers\StorePickup;
use PnShop\Shipping\Contracts\ShippingCarrier;
use PnShop\Shipping\Testing\ShippingCarrierContractTests;
use Tests\TestCase;

class StorePickupContractTest extends TestCase
{
    use RefreshDatabase, ShippingCarrierContractTests;

    protected function carrier(): ShippingCarrier
    {
        return new StorePickup;
    }
}
