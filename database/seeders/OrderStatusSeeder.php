<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PnShop\Sales\Models\OrderStatus;

class OrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['pending', 'paid', 'shipped', 'cancelled'] as $name) {
            OrderStatus::firstOrCreate(['name' => $name]);
        }
    }
}
