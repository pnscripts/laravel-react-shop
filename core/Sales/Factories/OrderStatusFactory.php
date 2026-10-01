<?php

namespace PnShop\Sales\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use PnShop\Sales\Models\OrderStatus;

/**
 * @extends Factory<OrderStatus>
 */
class OrderStatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<OrderStatus>
     */
    protected $model = OrderStatus::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['pending', 'processing', 'completed', 'cancelled']),
        ];
    }
}
