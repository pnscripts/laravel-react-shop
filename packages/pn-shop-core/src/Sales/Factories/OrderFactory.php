<?php

namespace PnShop\Sales\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use PnShop\Customer\Models\User;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Sales\Models\Order;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'payment_method_id' => PaymentMethod::factory(),
        ];
    }
}
