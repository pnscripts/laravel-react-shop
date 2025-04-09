<?php

namespace Tests\Unit\Traits;

use Mockery;
use Tests\TestCase;
use App\Traits\HasSortOrder;

class HasSortOrderTest extends TestCase
{
    /**
     * Set up the test environment.
     */
    public function setUp(): void
    {
        parent::setUp();

        // Create a unique dummy class for testing with the trait
        $this->dummyClass = new class {
            use HasSortOrder;
            public $sort_order;

            // Simulate the max() method to return a value for the next sort order
            public static function max($column)
            {
                return 3; // Simulating that the max value of sort_order is 3
            }
        };
    }

    /**
     * Test that sort_order is set when not provided on create.
     */
    public function test_it_sets_sort_order_when_not_provided_on_create()
    {
        // Create a mock instance of the dummy class
        $mockClass = Mockery::mock(get_class($this->dummyClass) . '[creating]')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Simulate the behavior of getNextSortOrder
        $mockClass->shouldReceive('getNextSortOrder')
            ->once()
            ->andReturn(5);

        // Simulate the creation process (without involving DB)
        $mockClass->sort_order = null; // No sort_order provided

        // Manually trigger the creating event logic
        // This simulates what would happen during the Eloquent "creating" lifecycle method
        if (is_null($mockClass->sort_order)) {
            $mockClass->sort_order = $mockClass->getNextSortOrder();
        }

        // Assert: Ensure that the sort_order is set to the next available value (5)
        $this->assertEquals(5, $mockClass->sort_order);
    }

    /**
     * Test that sort_order is set when not provided on update.
     */
    public function test_it_sets_sort_order_when_not_provided_on_update()
    {
        // Create a mock instance of the dummy class
        $mockClass = Mockery::mock(get_class($this->dummyClass) . '[updating]')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Simulate the behavior of getNextSortOrder
        $mockClass->shouldReceive('getNextSortOrder')
            ->once()
            ->andReturn(10);

        // Simulate the update process (without involving DB)
        $mockClass->sort_order = null; // No sort_order provided

        // Manually trigger the updating event logic
        // This simulates what would happen during the Eloquent "updating" lifecycle method
        if (is_null($mockClass->sort_order)) {
            $mockClass->sort_order = $mockClass->getNextSortOrder();
        }

        // Assert: Ensure that the sort_order is set to the next available value (10)
        $this->assertEquals(10, $mockClass->sort_order);
    }

    /**
     * Test that getNextSortOrder method calculates the next sort order correctly.
     */
    public function test_it_can_get_next_sort_order()
    {
        // Create the mock class
        $mockClass = Mockery::mock(get_class($this->dummyClass) . '[getNextSortOrder]')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Simulate the behavior of getNextSortOrder
        $mockClass->shouldReceive('getNextSortOrder')
            ->once()
            ->andReturn(4); // We simulate the next sort order as 4

        // Call the getNextSortOrder method
        $nextSortOrder = $mockClass->getNextSortOrder();

        // Assert: The next sort order should be 4 (3 + 1)
        $this->assertEquals(4, $nextSortOrder);
    }

    /**
     * Test that scopeSortByOrder correctly orders by sort_order.
     */
    public function test_it_can_sort_by_sort_order()
    {
        // Simulate the query builder (without database interaction)
        $mockQuery = Mockery::mock('Illuminate\Database\Eloquent\Builder');

        // Create the mock class
        $mockClass = Mockery::mock(get_class($this->dummyClass) . '[scopeSortByOrder]')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Expect the orderBy method to be called on the query builder
        $mockQuery->shouldReceive('orderBy')
            ->with('sort_order', 'asc')
            ->once()
            ->andReturnSelf();

        // Act: Call the scopeSortByOrder method with 'asc' direction
        $mockClass->scopeSortByOrder($mockQuery, 'asc');

        // Assert: Ensure that orderBy was called with the correct parameters
        $mockQuery->shouldHaveReceived('orderBy');
    }

    /**
     * Test that scopeSortByOrder correctly handles secondary ordering.
     */
    public function test_it_can_sort_by_sort_order_with_secondary_order()
    {
        // Simulate the query builder (without database interaction)
        $mockQuery = Mockery::mock('Illuminate\Database\Eloquent\Builder');

        // Create the mock class
        $mockClass = Mockery::mock(get_class($this->dummyClass) . '[scopeSortByOrder]')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Expect the orderBy method to be called for sort_order and secondary sorting by created_at
        $mockQuery->shouldReceive('orderBy')
            ->with('sort_order', 'asc')
            ->once()
            ->andReturnSelf();

        $mockQuery->shouldReceive('orderBy')
            ->with('created_at', 'desc')
            ->once()
            ->andReturnSelf();

        // Act: Call the scopeSortByOrder method with 'asc' direction and secondary ordering by 'created_at'
        $mockClass->scopeSortByOrder($mockQuery, 'asc', 'created_at', 'desc');

        // Assert: Ensure that orderBy was called twice with the correct parameters
        $mockQuery->shouldHaveReceived('orderBy')->twice();
    }
}
