<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Product>
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = $this->faker->randomFloat(2, 10, 5000);

        return [
            'product_category_id' => ProductCategory::query()->inRandomOrder()->value('id') ?? ProductCategory::factory(),
            'title' => $this->faker->words(3, true),
            'slug' => $this->faker->slug(),
            'description' => $this->faker->paragraph(),
            'price' => $price,
            'discount_price' => $this->faker->optional()->passthrough(round($price * $this->faker->randomFloat(2, 0.5, 0.95), 2)),
            'stock' => $this->faker->numberBetween(0, 100),
            'is_active' => $this->faker->boolean(90),
            'sku' => strtoupper($this->faker->bothify('SKU-####-???')),
            'barcode' => $this->faker->ean13(),
            'image' => $this->faker->imageUrl(600, 600, 'products', true),
        ];
    }

    /**
     * Indicate that the product is publicly visible.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the product is hidden from the storefront.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
