<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PaymentMethod::firstOrCreate(
            ['type' => 'cash_on_delivery'],
            [
                'name' => 'Cash on Delivery',
                'description' => 'Pay with cash when your order arrives.',
                'is_active' => true,
            ],
        );

        PaymentMethod::firstOrCreate(
            ['type' => 'bank_transfer'],
            [
                'name' => 'Bank Transfer',
                'description' => 'Pay by bank transfer. Instructions are sent after checkout.',
                'is_active' => true,
            ],
        );
    }
}
