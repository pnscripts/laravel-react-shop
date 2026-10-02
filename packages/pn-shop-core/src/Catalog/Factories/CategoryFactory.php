<?php

namespace PnShop\Catalog\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use PnShop\Catalog\Models\Category;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Category>
     */
    protected $model = Category::class;

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
     * Place the category under an existing (or new) parent category.
     */
    public function withParent(?Category $parent = null): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id ?? Category::factory()]);
    }
}
