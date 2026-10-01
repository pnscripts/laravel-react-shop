<?php

namespace PnShop\Payment\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use PnShop\Payment\Models\PaymentMethod;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PaymentMethod>
     */
    protected $model = PaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Cash on Delivery', 'Bank Transfer']),
            'description' => $this->faker->sentence,
            'gateway' => $this->faker->randomElement(['cash_on_delivery', 'bank_transfer']),
            'is_active' => true,
        ];
    }
}
