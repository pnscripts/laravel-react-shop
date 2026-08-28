<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use Illuminate\Database\Seeder;

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
