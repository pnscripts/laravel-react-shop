<?php

namespace PnShop\Shipping\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\Models\ShippingZone;

/**
 * @extends Factory<ShippingMethod>
 */
class ShippingMethodFactory extends Factory
{
    protected $model = ShippingMethod::class;

    public function definition(): array
    {
        return [
            'shipping_zone_id' => ShippingZone::factory(),
            'name' => 'Courier',
            'carrier' => 'flat_rate',
            'settings' => ['cost' => '5.00'],
            'is_active' => true,
        ];
    }
}
