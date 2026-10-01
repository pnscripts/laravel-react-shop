<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the 'Uncategorized' category without translation
        $uncategorized = ProductCategory::create([
            'title' => 'Uncategorized',
        ]);

        // Set translations for the 'title' field (only for 'Uncategorized')
        $uncategorized->setTranslation('title', 'en', 'Uncategorized');
        $uncategorized->setTranslation('title', 'bg', 'Некатегоризирани'); // Bulgarian translation for 'Uncategorized'

        // Create parent categories without translations
        $parentCategories = ProductCategory::factory(5)->create();

        // Create child categories for each parent category without translations
        $parentCategories->each(function ($parentCategory) {
            ProductCategory::factory(3)->create([
                'parent_id' => $parentCategory->id,
            ]);
        });

        // Create random child categories without translations
        ProductCategory::factory(5)->withParent()->create();
    }
}
