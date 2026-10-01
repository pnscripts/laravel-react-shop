<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        OrderStatus::factory()->create(['name' => 'pending']);
        $payment = PaymentMethod::factory()->create(['is_active' => true]);
        $this->product = Product::factory()->active()->create(['stock' => 5]);

        $this->post(route('cart.store'), ['product_id' => $this->product->id, 'quantity' => 3]);
        $this->post(route('checkout.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '0888123456',
            'address' => '123 Main St',
            'payment_method_id' => $payment->id,
        ]);

        $this->order = Order::query()->sole();
    }

    public function test_cancelling_an_order_returns_its_stock(): void
    {
        $cancelled = OrderStatus::factory()->create(['name' => 'cancelled']);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.orders.update', $this->order), ['order_status_id' => $cancelled->id])
            ->assertSessionHas('success');

        $this->assertSame(5, $this->product->fresh()->stock);
        $this->assertSame($cancelled->id, $this->order->fresh()->order_status_id);
    }

    public function test_reopening_a_cancelled_order_takes_stock_again_once(): void
    {
        $cancelled = OrderStatus::factory()->create(['name' => 'cancelled']);
        $paid = OrderStatus::factory()->create(['name' => 'paid']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.orders.update', $this->order), ['order_status_id' => $cancelled->id]);
        $this->actingAs($admin)->patch(route('admin.orders.update', $this->order), ['order_status_id' => $cancelled->id]);
        $this->assertSame(5, $this->product->fresh()->stock);

        $this->actingAs($admin)->patch(route('admin.orders.update', $this->order), ['order_status_id' => $paid->id]);
        $this->assertSame(2, $this->product->fresh()->stock);
    }

    public function test_reopening_fails_when_stock_is_gone(): void
    {
        $cancelled = OrderStatus::factory()->create(['name' => 'cancelled']);
        $paid = OrderStatus::factory()->create(['name' => 'paid']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.orders.update', $this->order), ['order_status_id' => $cancelled->id]);
        $this->product->update(['stock' => 1]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $this->order), ['order_status_id' => $paid->id])
            ->assertSessionHas('error');

        $this->assertSame(1, $this->product->fresh()->stock);
        $this->assertSame($cancelled->id, $this->order->fresh()->order_status_id);
    }
}
