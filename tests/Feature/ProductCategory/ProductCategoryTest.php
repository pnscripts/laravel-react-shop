<?php

namespace Tests\Feature\ProductCategory;

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a product category with an automatically generated slug.
     *
     * Arrange: Create a product category with an empty slug.
     * Act: Create the category and check its generated slug.
     * Assert: Ensure the slug is generated based on the category title.
     */
    public function test_it_can_create_a_product_category_with_a_generated_slug()
    {
        // Arrange
        $category = ProductCategory::create([
            'title' => 'Test Category',
            'slug' => '', // Empty slug, should generate
        ]);

        // Act
        $generatedSlug = $category->slug;

        // Assert
        $this->assertEquals(Str::slug('Test Category'), $generatedSlug);
    }

    /**
     * Test that the slug is unique when creating multiple categories with the same title.
     *
     * Arrange: Create the first category with a given title.
     * Act: Create a second category with the same title and check its slug.
     * Assert: Ensure that the second category gets a unique slug.
     */
    public function test_it_ensures_the_slug_is_unique_when_creating_multiple_categories()
    {
        // Arrange
        $category1 = ProductCategory::create(['title' => 'Test Category']);
        $category2 = ProductCategory::create(['title' => 'Test Category']); // Same title

        // Act
        $uniqueSlug = $category2->slug;

        // Assert
        $this->assertEquals(Str::slug('Test Category').'-1', $uniqueSlug);
    }

    public function test_it_can_create_a_product_category_with_translations(): void
    {
        $category = ProductCategory::create(['title' => 'Uncategorized']);
        $category->setTranslations('bg', ['title' => 'Некатегоризирани']);

        $this->assertSame('Некатегоризирани', $category->translation('title', 'bg'));
        $this->assertSame('nekategorizirani', $category->translation('slug', 'bg'));
        $this->assertNull($category->translation('title', 'en'), 'The default language lives in the category itself.');
    }

    public function test_attributes_follow_the_current_locale_and_fall_back_to_the_source_language(): void
    {
        $category = ProductCategory::create(['title' => 'Uncategorized']);
        $category->setTranslations('bg', ['title' => 'Некатегоризирани']);

        app()->setLocale('bg');
        $this->assertSame('Некатегоризирани', ProductCategory::query()->find($category->id)->title);

        app()->setLocale('de');
        $this->assertSame('Uncategorized', ProductCategory::query()->find($category->id)->title);

        app()->setLocale('en');
        $this->assertSame('Uncategorized', ProductCategory::query()->find($category->id)->title);
    }

    /**
     * Test filtering categories by active status.
     *
     * Arrange: Create an active category.
     * Act: Use the scope to filter categories by active status.
     * Assert: Ensure the active category is returned.
     */
    public function test_it_can_filter_categories_by_active_status()
    {
        // Arrange
        $category = ProductCategory::create(['title' => 'Active Category', 'is_active' => true]);

        // Act
        $result = ProductCategory::whereActive(true)->get();

        // Assert
        $this->assertTrue($result->contains($category));
    }

    /**
     * Test filtering categories by parent category.
     *
     * Arrange: Create a parent category and a child category.
     * Act: Use the scope to filter by parent category.
     * Assert: Ensure only the child category of the specified parent is returned.
     */
    public function test_it_can_filter_categories_by_parent_category()
    {
        // Arrange
        $parentCategory = ProductCategory::create(['title' => 'Parent Category']);
        $childCategory = ProductCategory::create(['title' => 'Child Category', 'parent_id' => $parentCategory->id]);

        // Act
        $result = ProductCategory::byParent($parentCategory->id)->get();

        // Assert
        $this->assertTrue($result->contains($childCategory));
    }

    /**
     * Test filtering categories by slug.
     *
     * Arrange: Create a category with a specific slug.
     * Act: Use the scope to filter categories by the slug.
     * Assert: Ensure the category with the specified slug is returned.
     */
    public function test_it_can_filter_categories_by_slug()
    {
        // Arrange
        $category = ProductCategory::create(['title' => 'Test Slug', 'slug' => 'test-slug']);

        // Act
        $result = ProductCategory::bySlug('test-slug')->get();

        // Assert
        $this->assertTrue($result->contains($category));
    }

    /**
     * Test filtering categories by title.
     *
     * Arrange: Create a category with a specific title.
     * Act: Use the scope to filter categories by the title.
     * Assert: Ensure the category with the specified title is returned.
     */
    public function test_it_can_filter_categories_by_title()
    {
        // Arrange
        $category = ProductCategory::create(['title' => 'Test Title']);

        // Act
        $result = ProductCategory::byTitle('Test Title')->get();

        // Assert
        $this->assertTrue($result->contains($category));
    }
}
