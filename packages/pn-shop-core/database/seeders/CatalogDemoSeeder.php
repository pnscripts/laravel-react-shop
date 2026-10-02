<?php

namespace PnShop\Database\Seeders;

use Illuminate\Database\Seeder;
use PnShop\Catalog\Models\Brand;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Option;
use PnShop\Catalog\Models\Product;
use PnShop\Catalog\Models\ProductVariant;
use PnShop\Catalog\ProductType;
use PnShop\Inventory\InventoryService;

/**
 * Demo brands and products with variants, to show the catalog features.
 */
class CatalogDemoSeeder extends Seeder
{
    public function run(InventoryService $inventory): void
    {
        $brands = collect(['Northwind', 'Lumen Works', 'Oakline'])->map(fn (string $name) => Brand::query()->create(['name' => $name]));

        Product::query()->inRandomOrder()->limit(20)->get()->each(
            fn (Product $product) => $product->update(['brand_id' => $brands->random()->id]),
        );

        $size = Option::query()->create(['code' => 'size', 'name' => 'Size']);
        $size->setTranslations('bg', ['name' => 'Размер']);
        $values = collect(['S', 'M', 'L', 'XL'])->map(fn (string $value, int $position) => $size->values()->create(['value' => $value, 'position' => $position]));

        $apparel = Category::query()->create(['title' => 'Apparel']);
        $apparel->setTranslations('bg', ['title' => 'Облекло']);

        foreach (['Classic T-shirt' => 'Класическа тениска', 'Organic Hoodie' => 'Органичен суитшърт', 'Linen Shirt' => 'Ленена риза'] as $title => $bgTitle) {
            $product = Product::query()->create([
                'type' => ProductType::Variable,
                'title' => $title,
                'description' => fake()->paragraph(),
                'product_category_id' => $apparel->id,
                'brand_id' => $brands->random()->id,
                'is_active' => true,
            ]);
            $product->setTranslations('bg', ['title' => $bgTitle]);
            $product->options()->attach($size);

            $base = fake()->numberBetween(20, 60);

            foreach ($values as $index => $value) {
                $variant = ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'sku' => strtoupper(substr($title, 0, 3)).'-'.$value->value.'-'.$product->id,
                    'price' => $base + ($index >= 2 ? 5 : 0).'.00',
                    'position' => $index,
                ]);
                $variant->optionValues()->sync([$value->id]);
                $inventory->setOnHand($variant, fake()->numberBetween(0, 12));
            }
        }
    }
}
