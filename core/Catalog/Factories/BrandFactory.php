<?php

namespace PnShop\Catalog\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use PnShop\Catalog\Models\Brand;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    /** @var class-string<Brand> */
    protected $model = Brand::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
