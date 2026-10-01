<?php

namespace Database\Factories;

use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<ProductAttribute>
 */
class ProductAttributeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ProductAttribute>
     */
    protected $model = ProductAttribute::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->faker->word, // e.g., 'wifi', 'size'
            'label' => $this->faker->word, // e.g., 'Wi-Fi', 'Size'
            'type' => $this->faker->randomElement(['text', 'select', 'boolean']),
            'is_required' => $this->faker->boolean,
        ];
    }
}
