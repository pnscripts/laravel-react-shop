<?php

namespace PnShop\Promotion\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use PnShop\Promotion\Models\Promotion;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->word()).' sale',
            'is_active' => true,
            'conditions' => [],
            'actions' => [['type' => 'percent_off', 'data' => ['percent' => 10]]],
        ];
    }
}
