<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if any product categories exist
        $categories = ProductCategory::all();

        if ($categories->isEmpty()) {
            // If no categories exist, print a message and return
            $this->command->info('No product categories found. Skipping Product Attribute creation.');
            return;
        }

        // If categories exist, create product attributes using factories
        $attributesData = [
            ['key' => 'wifi', 'label' => 'Wi-Fi', 'type' => 'boolean', 'is_required' => false],
            ['key' => 'size', 'label' => 'Size', 'type' => 'select', 'is_required' => true],
            ['key' => 'color', 'label' => 'Color', 'type' => 'select', 'is_required' => true],
        ];

        foreach ($attributesData as $attributeData) {
            // Create the ProductAttribute using the factory
            $attribute = ProductAttribute::factory()->create([
                'key' => $attributeData['key'],
                'label' => $attributeData['label'],
                'type' => $attributeData['type'],
                'is_required' => $attributeData['is_required'],
            ]);

            // Define attribute values based on the attribute key
            $values = [];
            if ($attribute->key == 'size') {
                $values = ['S', 'M', 'L', 'XL'];
            } elseif ($attribute->key == 'color') {
                $values = ['Red', 'Green', 'Blue', 'Black'];
            } elseif ($attribute->key == 'wifi') {
                $values = ['true', 'false'];
            }

            // Create ProductAttributeValue records using the factory
            foreach ($values as $value) {
                ProductAttributeValue::factory()->create([
                    'product_attribute_id' => $attribute->id,
                    'value' => $value,
                ]);
            }

            // Attach this attribute to all existing categories
            foreach ($categories as $category) {
                $category->productAttributes()->attach($attribute);
            }

            $this->command->info("Product Attribute '{$attribute->label}' created and attached to categories.");
        }
    }
}
