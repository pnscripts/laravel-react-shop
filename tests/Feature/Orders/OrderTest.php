<?php

namespace Tests\Feature\Orders;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Models\PaymentMethod;
use PnShop\Sales\States\OrderStatus;
use PnShop\Settings\Settings;
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
        $payment = PaymentMethod::factory()->create(['name' => 'paypal']);

        // Act
        $order = Order::create([
            'user_id' => $user->id,
            'name' => 'John Doe',
            'address' => '123 Main St',
            'phone' => '0888123456',
            'email' => 'john@example.com',
            'payment_method_id' => $payment->id,
        ]);

        // Assert
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertEquals('paypal', $order->paymentMethod->name);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame('ORD-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT), $order->fresh()->number);
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

    public function test_order_numbers_follow_the_settings(): void
    {
        app(Settings::class)->set('sales', ['order_number_prefix' => 'BG-', 'order_number_digits' => 4]);

        $order = Order::factory()->create();

        $this->assertSame('BG-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT), $order->number);
    }
}
