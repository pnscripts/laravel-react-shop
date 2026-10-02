<?php

namespace PnShop\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed reference data, plus a demo catalog outside production.
     *
     * No user accounts are seeded. Create an administrator with `php artisan pnshop:create-admin`.
     */
    public function run(): void
    {
        $this->call([
            PaymentMethodSeeder::class,
            ShippingSeeder::class,
            TaxSeeder::class,
        ]);

        if (app()->isProduction()) {
            return;
        }

        $this->call([
            ProductCategorySeeder::class,
            ProductAttributeSeeder::class,
            ProductSeeder::class,
            CatalogDemoSeeder::class,
        ]);
    }
}
