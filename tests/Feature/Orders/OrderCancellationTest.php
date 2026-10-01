<?php

namespace Tests\Feature\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Catalog\Models\Product;
use PnShop\Sales\Exceptions\CheckoutException;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Models\OrderStatus;
use PnShop\Sales\Models\PaymentMethod;
use PnShop\Sales\OrderStatusService;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private Order $order;

    private OrderStatusService $statuses;

    protected function setUp(): void
    {
        parent::setUp();

        OrderStatus::factory()->create(['name' => 'pending']);
        $payment = PaymentMethod::factory()->create(['is_active' => true]);
        $this->product = Product::factory()->active()->create(['stock' => 5]);

        $this->post(route('cart.store'), ['product_id' => $this->product->id, 'quantity' => 3]);
        $this->post(route('checkout.store'), $this->checkoutData($payment->id, ['email' => 'jane@example.com']));

        $this->order = Order::query()->sole();
        $this->statuses = app(OrderStatusService::class);
    }

    public function test_cancelling_an_order_returns_its_stock(): void
    {
        $cancelled = OrderStatus::factory()->create(['name' => 'cancelled']);

        $this->statuses->change($this->order, $cancelled);

        $this->assertSame(5, $this->product->fresh()->stock);
        $this->assertSame($cancelled->id, $this->order->fresh()->order_status_id);
    }

    public function test_reopening_a_cancelled_order_takes_stock_again_once(): void
    {
        $cancelled = OrderStatus::factory()->create(['name' => 'cancelled']);
        $paid = OrderStatus::factory()->create(['name' => 'paid']);

        $this->statuses->change($this->order, $cancelled);
        $this->statuses->change($this->order, $cancelled);
        $this->assertSame(5, $this->product->fresh()->stock);

        $this->statuses->change($this->order, $paid);
        $this->assertSame(2, $this->product->fresh()->stock);
    }

    public function test_reopening_fails_when_stock_is_gone(): void
    {
        $cancelled = OrderStatus::factory()->create(['name' => 'cancelled']);
        $paid = OrderStatus::factory()->create(['name' => 'paid']);

        $this->statuses->change($this->order, $cancelled);
        $this->product->update(['stock' => 1]);

        try {
            $this->statuses->change($this->order, $paid);
            $this->fail('Reopening without stock should be refused.');
        } catch (CheckoutException) {
            //
        }

        $this->assertSame(1, $this->product->fresh()->stock);
        $this->assertSame($cancelled->id, $this->order->fresh()->order_status_id);
    }
}
