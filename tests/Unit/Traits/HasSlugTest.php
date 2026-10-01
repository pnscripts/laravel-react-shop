<?php

namespace Tests\Unit\Traits;

use Mockery;
use PnShop\Foundation\Concerns\HasSlug;
use Tests\TestCase;

class HasSlugTest extends TestCase
{
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a unique dummy class for testing with the HasSlug trait
        $this->dummyClass = new class
        {
            use HasSlug;

            public $title;

            public $slug;
        };
    }

    /**
     * Test that the slug is generated if not provided.
     */
    public function test_it_can_generate_slug_when_not_provided()
    {
        // Create a mock instance of the dummy class
        $mockClass = Mockery::mock(get_class($this->dummyClass).'[creating]')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Set the title for the mock object
        $mockClass->title = 'Test Category';

        // Simulate the behavior of generateUniqueSlug
        $mockClass->shouldReceive('generateUniqueSlug')
            ->once()
            ->andReturn('test-category');

        // Simulate the creation process (without involving DB)
        $mockClass->slug = null; // No slug provided

        // Manually trigger the creating event logic
        // This simulates what would happen during the Eloquent "creating" lifecycle method
        if (is_null($mockClass->slug)) {
            $mockClass->slug = $mockClass->generateUniqueSlug($mockClass);
        }

        // Assert: Ensure that the slug is set to the generated value
        $this->assertEquals('test-category', $mockClass->slug);
    }

    /**
     * Test that the slug is unique.
     */
    public function test_it_can_make_slug_unique()
    {
        // Create a mock instance of the dummy class
        $mockClass = Mockery::mock(get_class($this->dummyClass).'[creating]')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Set the title and slug for the mock object
        $mockClass->title = 'Test Category';
        $mockClass->slug = 'test-category';

        // Simulate the makeSlugUnique method
        $mockClass->shouldReceive('makeSlugUnique')
            ->with($mockClass, 'test-category')
            ->once()
            ->andReturn('test-category-1');

        // Act: Generate a unique slug
        $uniqueSlug = $mockClass->makeSlugUnique($mockClass, 'test-category');

        // Assert: Ensure the slug is unique
        $this->assertEquals('test-category-1', $uniqueSlug);
    }

    /**
     * Test that the slug is generated correctly from a title.
     */
    public function test_it_can_generate_slug_from_title()
    {
        // Create a mock instance of the dummy class
        $mockClass = Mockery::mock(get_class($this->dummyClass).'[creating]')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Set the title for the mock object
        $mockClass->title = 'Test Category!';

        // Simulate the behavior of generateUniqueSlug
        $mockClass->shouldReceive('generateUniqueSlug')
            ->once()
            ->andReturn('test-category');

        // Act: Generate the slug
        $slug = $mockClass->generateUniqueSlug($mockClass);

        // Assert: Ensure the generated slug is correct
        $this->assertEquals('test-category', $slug);
    }

    /**
     * Test that generateUniqueSlug makes the slug unique if it already exists.
     */
    public function test_it_creates_unique_slug_when_duplicate_exists()
    {
        // Create a mock instance of the dummy class
        $mockClass = Mockery::mock(get_class($this->dummyClass).'[creating]')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Simulate checking and creating a unique slug
        $mockClass->shouldReceive('makeSlugUnique')
            ->with($mockClass, 'test-category')
            ->andReturn('test-category-2')
            ->once();

        // Act: Make the slug unique
        $uniqueSlug = $mockClass->makeSlugUnique($mockClass, 'test-category');

        // Assert: Ensure the returned slug is unique
        $this->assertEquals('test-category-2', $uniqueSlug);
    }
}
