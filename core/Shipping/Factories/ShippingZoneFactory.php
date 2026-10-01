<?php

namespace PnShop\Shipping\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use PnShop\Shipping\Models\ShippingZone;

/**
 * @extends Factory<ShippingZone>
 */
class ShippingZoneFactory extends Factory
{
    protected $model = ShippingZone::class;

    public function definition(): array
    {
        return ['name' => $this->faker->country(), 'countries' => null, 'postcodes' => null, 'position' => 0];
    }
}
