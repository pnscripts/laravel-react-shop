<?php

namespace PnShop\Catalog\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use PnShop\Catalog\Models\ProductAttribute;
use PnShop\Catalog\Models\ProductAttributeValue;

/**
 * @extends Factory<ProductAttributeValue>
 */
class ProductAttributeValueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ProductAttributeValue>
     */
    protected $model = ProductAttributeValue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_attribute_id' => ProductAttribute::factory(), // Associate with a ProductAttribute
            'value' => $this->faker->word, // e.g., 'True', 'Large', 'Red'
        ];
    }
}
