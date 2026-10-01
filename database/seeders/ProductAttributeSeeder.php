<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\ProductAttribute;
use PnShop\Catalog\Models\ProductAttributeValue;

class ProductAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if any product categories exist
        $categories = Category::all();

        if ($categories->isEmpty()) {
            // If no categories exist, print a message and return
            $this->command->info('No product categories found. Skipping Product Attribute creation.');

            return;
        }

        // If categories exist, create product attributes using factories
        // Specification attributes (filterable in the shop). Size is a variant option, see CatalogDemoSeeder.
        $attributesData = [
            ['key' => 'wifi', 'label' => 'Wi-Fi', 'bg' => 'Wi-Fi', 'type' => 'boolean', 'values' => ['Yes' => 'Да', 'No' => 'Не']],
            ['key' => 'material', 'label' => 'Material', 'bg' => 'Материал', 'type' => 'select', 'values' => ['Cotton' => 'Памук', 'Wood' => 'Дърво', 'Metal' => 'Метал']],
            ['key' => 'color', 'label' => 'Color', 'bg' => 'Цвят', 'type' => 'select', 'values' => ['Red' => 'Червен', 'Green' => 'Зелен', 'Blue' => 'Син', 'Black' => 'Черен']],
        ];

        foreach ($attributesData as $position => $attributeData) {
            $attribute = ProductAttribute::factory()->create([
                'key' => $attributeData['key'],
                'label' => $attributeData['label'],
                'type' => $attributeData['type'],
                'is_required' => false,
                'is_filterable' => true,
                'position' => $position,
            ]);
            $attribute->setTranslations('bg', ['label' => $attributeData['bg']]);

            foreach (array_keys($attributeData['values']) as $valuePosition => $value) {
                $created = ProductAttributeValue::factory()->create([
                    'product_attribute_id' => $attribute->id,
                    'value' => $value,
                    'position' => $valuePosition,
                ]);
                $created->setTranslations('bg', ['value' => $attributeData['values'][$value]]);
            }

            // Attach this attribute to all existing categories
            foreach ($categories as $category) {
                $category->productAttributes()->attach($attribute);
            }

            $this->command->info("Product Attribute '{$attribute->label}' created and attached to categories.");
        }
    }
}
