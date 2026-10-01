<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PnShop\Tax\Models\TaxClass;

class TaxSeeder extends Seeder
{
    /**
     * The usual tax classes. Zones and rates depend on where the store sells, so none are
     * created: until the merchant adds them under Admin → Store → Tax zones, nothing is taxed.
     */
    public function run(): void
    {
        foreach (['Standard' => true, 'Reduced' => false, 'Zero rate' => false] as $name => $isDefault) {
            TaxClass::query()->firstOrCreate(['name' => $name], ['is_default' => $isDefault]);
        }
    }
}
