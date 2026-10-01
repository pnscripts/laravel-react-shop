<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PnShop\Catalog\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::factory()
            ->count(30)
            ->create()
            ->load('category.productAttributes.values')
            ->each(function ($product) {
                $category = $product->category;

                // Attach random attribute values to the product
                if ($category && $category->productAttributes->isNotEmpty()) {
                    $category->productAttributes->each(function ($attribute) use ($product) {
                        if ($attribute->values->isNotEmpty()) {
                            $randomValue = $attribute->values->random();
                            $product->selectedAttributeValues()->attach($randomValue->id);
                        }
                    });
                }
            });
    }
}
