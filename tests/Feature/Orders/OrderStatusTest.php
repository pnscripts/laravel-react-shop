<?php

namespace Tests\Feature\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Sales\Models\OrderStatus;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    /** Test order status creation using the factory. */
    public function test_order_status_can_be_created(): void
    {
        // Arrange & Act
        $status = OrderStatus::factory()->create([
            'name' => 'pending',
        ]);

        // Assert
        $this->assertDatabaseHas('order_statuses', [
            'id' => $status->id,
            'name' => 'pending',
        ]);
    }

    /** Test soft delete on order status. */
    public function test_order_status_can_be_soft_deleted(): void
    {
        // Arrange
        $status = OrderStatus::factory()->create();

        // Act
        $status->delete();

        // Assert
        $this->assertSoftDeleted($status);
    }
}
