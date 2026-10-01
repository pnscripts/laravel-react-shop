<?php

namespace Tests\Unit\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Catalog\Models\Category;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the parent category relationship.
     *
     * Arrange: Create a parent category and a child category.
     * Act: Check if the child category has the correct parent.
     * Assert: Ensure the child category belongs to the correct parent.
     */
    public function test_it_has_a_parent_category()
    {
        // Arrange
        $parent = Category::create(['title' => 'Parent Category']);
        $child = Category::create(['title' => 'Child Category', 'parent_id' => $parent->id]);

        // Act
        $parentRelation = $child->parent();

        // Assert
        $this->assertInstanceOf(BelongsTo::class, $parentRelation);
        $this->assertEquals($parent->id, $child->parent->id);
    }

    /**
     * Test the children category relationship.
     *
     * Arrange: Create a parent category and multiple child categories.
     * Act: Check if the parent category has the correct children.
     * Assert: Ensure the parent category has the expected number of children.
     */
    public function test_it_has_children_categories()
    {
        // Arrange
        $parent = Category::create(['title' => 'Parent Category']);
        $child1 = Category::create(['title' => 'Child Category 1', 'parent_id' => $parent->id]);
        $child2 = Category::create(['title' => 'Child Category 2', 'parent_id' => $parent->id]);

        // Act (nested sets: reload the parent after inserting children)
        $parent->refresh();
        $children = $parent->children();

        // Assert
        $this->assertInstanceOf(HasMany::class, $children);
        $this->assertCount(2, $parent->children);
    }

    /**
     * Test getting the translated attribute.
     *
     * Arrange: Create a category and set translations for multiple locales.
     * Act: Retrieve the translations for the title field.
     * Assert: Ensure the correct translations are returned for each locale.
     */
    public function test_it_can_get_translated_attributes()
    {
        // Arrange
        $category = Category::create(['title' => 'Uncategorized']);
        $category->setTranslations('bg', ['title' => 'Некатегоризирани']);

        // Act
        $bgTranslation = $category->translation('title', 'bg');
        $enTranslation = $category->title;

        // Assert
        $this->assertEquals('Некатегоризирани', $bgTranslation);
        $this->assertEquals('Uncategorized', $enTranslation);
    }
}
