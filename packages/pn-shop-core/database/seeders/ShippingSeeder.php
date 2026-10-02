<?php

namespace PnShop\Database\Seeders;

use Illuminate\Database\Seeder;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\Models\ShippingZone;

class ShippingSeeder extends Seeder
{
    /**
     * One zone for every country with standard delivery, free delivery from 100 and
     * pickup, in English and Bulgarian. Adjust under Admin → Store → Shipping.
     */
    public function run(): void
    {
        if (ShippingZone::query()->exists()) {
            return;
        }

        $zone = ShippingZone::query()->create(['name' => 'Everywhere', 'position' => 100]);

        $methods = [
            ['Standard delivery', 'Delivered in 2–4 working days.', 'flat_rate', ['cost' => '5.00'], ['name' => 'Стандартна доставка', 'description' => 'Доставка до 2–4 работни дни.']],
            ['Free delivery', 'For orders from 100.', 'free_shipping', ['min_subtotal' => '100'], ['name' => 'Безплатна доставка', 'description' => 'За поръчки над 100.']],
            ['Pickup from the store', 'Collect your order from our shop.', 'pickup', [], ['name' => 'Вземане от магазина', 'description' => 'Вземете поръчката си от нашия магазин.']],
        ];

        foreach ($methods as $position => [$name, $description, $carrier, $settings, $bg]) {
            ShippingMethod::query()
                ->create(['shipping_zone_id' => $zone->id, 'name' => $name, 'description' => $description, 'carrier' => $carrier, 'settings' => $settings, 'position' => $position])
                ->setTranslations('bg', $bg);
        }
    }
}
