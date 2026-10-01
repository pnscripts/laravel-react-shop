<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ProductCategory>
     */
    protected $model = ProductCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->word(),
        ];
    }

    /**
     * Indicate that the category should have a parent category.
     *
     * @return Factory
     */
    public function withParent()
    {
        return $this->state(function (array $attributes) {
            return [
                'parent_id' => ProductCategory::all()->random()->id, // Randomly selects a parent category
            ];
        });
    }
}
