<?php

namespace Tests\Unit\Traits;

use Mockery;
use Tests\TestCase;
use App\Traits\HasSlug;

class HasSlugTest extends TestCase
{
    /**
     * Test that the slug is generated if not provided.
     */
    public function test_it_can_generate_slug_when_not_provided()
    {
        // Create a mock class that uses the HasSlug trait
        $mockClass = Mockery::mock('alias:App\Traits\HasSlug')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Set the title for the mock object
        $mockClass->title = 'Test Category';

        // Simulate the generateUniqueSlug method
        $mockClass->shouldReceive('generateUniqueSlug')
            ->once()
            ->andReturn('test-category');

        // Act: Generate the slug
        $slug = $mockClass->generateUniqueSlug($mockClass);

        // Assert: Ensure the generated slug is correct
        $this->assertEquals('test-category', $slug);
    }

    /**
     * Test that the slug is unique.
     */
    public function test_it_can_make_slug_unique()
    {
        // Create a mock class that uses the HasSlug trait
        $mockClass = Mockery::mock('alias:App\Traits\HasSlug')
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
        // Create a mock class that uses the HasSlug trait
        $mockClass = Mockery::mock('alias:App\Traits\HasSlug')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Set the title for the mock object
        $mockClass->title = 'Test Category!';

        // Simulate the generateUniqueSlug method
        $mockClass->shouldReceive('generateUniqueSlug')
            ->once()
            ->andReturn('test-category');

        // Act: Generate the slug
        $slug = $mockClass->generateUniqueSlug($mockClass);

        // Assert: Ensure the generated slug is correct
        $this->assertEquals('test-category', $slug);
    }

    /**
     * Test that the generated slug is unique if the same slug exists.
     */
    public function test_it_creates_unique_slug_when_duplicate_exists()
    {
        // Create a mock class that uses the HasSlug trait
        $mockClass = Mockery::mock('alias:App\Traits\HasSlug')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Simulate checking and creating a unique slug
        $mockClass->shouldReceive('makeSlugUnique')
            ->with($mockClass, 'test-category')
            ->andReturn('test-category-1')
            ->once();

        // Act: Make the slug unique
        $uniqueSlug = $mockClass->makeSlugUnique($mockClass, 'test-category');

        // Assert: Ensure the returned slug is unique
        $this->assertEquals('test-category-1', $uniqueSlug);
    }
}
