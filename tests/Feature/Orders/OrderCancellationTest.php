<?php

namespace Tests\Feature\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Catalog\Models\Product;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Sales\Exceptions\OrderException;
use PnShop\Sales\Models\Order;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\OrderStatus;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private Order $order;

    private OrderWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $payment = PaymentMethod::factory()->create(['is_active' => true]);
        $this->product = Product::factory()->active()->create(['stock' => 5]);

        $this->post(route('cart.store'), ['product_id' => $this->product->id, 'quantity' => 3]);
        $this->post(route('checkout.store'), $this->checkoutData($payment->id, ['email' => 'jane@example.com']));

        $this->order = Order::query()->sole();
        $this->workflow = app(OrderWorkflow::class);
    }

    public function test_cancelling_an_order_returns_its_stock(): void
    {
        $this->workflow->transition($this->order, OrderStatus::Cancelled);

        $this->assertSame(5, $this->product->fresh()->stock);
        $this->assertSame(OrderStatus::Cancelled, $this->order->fresh()->status);
    }

    public function test_reopening_a_cancelled_order_takes_stock_again_once(): void
    {
        $this->workflow->transition($this->order, OrderStatus::Cancelled);
        $this->workflow->transition($this->order, OrderStatus::Cancelled);
        $this->assertSame(5, $this->product->fresh()->stock);

        $this->workflow->transition($this->order, OrderStatus::Pending);
        $this->assertSame(2, $this->product->fresh()->stock);
    }

    public function test_reopening_fails_when_stock_is_gone(): void
    {
        $this->workflow->transition($this->order, OrderStatus::Cancelled);
        $this->product->update(['stock' => 1]);

        try {
            $this->workflow->transition($this->order, OrderStatus::Pending);
            $this->fail('Reopening without stock should be refused.');
        } catch (OrderException) {
            //
        }

        $this->assertSame(1, $this->product->fresh()->stock);
        $this->assertSame(OrderStatus::Cancelled, $this->order->fresh()->status);
    }
}
