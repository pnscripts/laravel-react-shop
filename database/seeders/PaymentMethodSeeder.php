<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PnShop\Payment\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    /**
     * The two built-in payment methods, with Bulgarian names. Bank details are left
     * for the merchant to fill in under Admin → Store → Payment methods.
     */
    public function run(): void
    {
        $methods = [
            'cash_on_delivery' => [
                'name' => 'Cash on delivery',
                'description' => 'Pay with cash when your order arrives.',
                'position' => 1,
                'bg' => ['name' => 'Наложен платеж', 'description' => 'Платете в брой при доставката.'],
            ],
            'bank_transfer' => [
                'name' => 'Bank transfer',
                'description' => 'Pay by bank transfer. The bank details are shown after checkout.',
                'position' => 2,
                'bg' => ['name' => 'Банков превод', 'description' => 'Платете с банков превод. Данните за превода се показват след поръчката.'],
            ],
        ];

        foreach ($methods as $gateway => $data) {
            $method = PaymentMethod::query()->firstOrCreate(
                ['gateway' => $gateway],
                ['name' => $data['name'], 'description' => $data['description'], 'position' => $data['position'], 'is_active' => true],
            );

            if (! $method->hasTranslation('bg')) {
                $method->setTranslations('bg', $data['bg']);
            }
        }
    }
}
