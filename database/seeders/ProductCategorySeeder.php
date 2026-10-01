<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PnShop\Catalog\Models\Category;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the 'Uncategorized' category without translation
        $uncategorized = Category::create([
            'title' => 'Uncategorized',
        ]);

        // Set translations for the 'title' field (only for 'Uncategorized')
        $uncategorized->setTranslations('bg', ['title' => 'Некатегоризирани']);

        // Create parent categories without translations
        $parentCategories = Category::factory(5)->create();

        // Create child categories for each parent category without translations
        $parentCategories->each(function ($parentCategory) {
            Category::factory(3)->create([
                'parent_id' => $parentCategory->id,
            ]);
        });

        // Create random child categories without translations
        Category::factory(5)->withParent(Category::query()->inRandomOrder()->first())->create();
    }
}
