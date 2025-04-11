<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test order creation with required fields and relationships.
     */
    public function test_order_can_be_created(): void
    {
        // Arrange
        $user = User::factory()->create();
        $status = OrderStatus::factory()->create(['name' => 'pending']);
        $payment = PaymentMethod::factory()->create(['name' => 'paypal']);

        // Act
        $order = Order::create([
            'user_id' => $user->id,
            'name' => 'John Doe',
            'address' => '123 Main St',
            'phone' => '0888123456',
            'email' => 'john@example.com',
            'order_status_id' => $status->id,
            'payment_method_id' => $payment->id,
        ]);

        // Assert
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertEquals('paypal', $order->paymentMethod->name);
        $this->assertEquals('pending', $order->orderStatus->name);
    }

    /**
     * Test that an order belongs to a user.
     */
    public function test_order_belongs_to_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();

        // Assert
        $this->assertTrue($order->user->is($user));
    }

    /**
     * Test soft deleting an order.
     */
    public function test_order_can_be_soft_deleted(): void
    {
        // Arrange
        $order = Order::factory()->create();

        // Act
        $order->delete();

        // Assert
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    /**
     * Test updating the status of an order.
     */
    public function test_order_status_can_be_updated(): void
    {
        // Arrange
        $statusOld = OrderStatus::factory()->create(['name' => 'pending']);
        $statusNew = OrderStatus::factory()->create(['name' => 'completed']);
        $order = Order::factory()->create(['order_status_id' => $statusOld->id]);

        // Act
        $order->update(['order_status_id' => $statusNew->id]);

        // Assert
        $this->assertEquals('completed', $order->fresh()->orderStatus->name);
    }
}
