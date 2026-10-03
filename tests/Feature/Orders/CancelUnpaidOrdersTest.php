<?php

namespace Tests\Feature\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Catalog\Models\Product;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Sales\Models\Order;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\FulfillmentStatus;
use PnShop\Sales\States\OrderStatus;
use PnShop\Sales\States\PaymentStatus;
use PnShop\Settings\Settings;
use PnShop\Shipping\ShipmentService;
use Tests\TestCase;

class CancelUnpaidOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function order(Product $product): Order
    {
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->post(route('checkout.store'), $this->checkoutData(PaymentMethod::factory()->create(['gateway' => 'bank_transfer'])->id))->assertSessionMissing('error');

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_unpaid_orders_are_cancelled_after_the_set_time_and_release_stock(): void
    {
        $product = Product::factory()->active()->create(['stock' => 5]);
        $unpaid = $this->order($product);
        $paid = $this->order($product);
        app(OrderWorkflow::class)->transition($paid, PaymentStatus::Paid);

        $this->assertSame(1, $product->defaultVariant()->available());

        $this->travel(167)->hours();
        $this->artisan('pnshop:orders:cancel-unpaid')->expectsOutputToContain('Cancelled 0')->assertSuccessful();

        $this->travel(2)->hours();
        $this->artisan('pnshop:orders:cancel-unpaid', ['--dry-run' => true])->expectsOutputToContain('Would cancel 1')->assertSuccessful();
        $this->artisan('pnshop:orders:cancel-unpaid')->expectsOutputToContain('Cancelled 1')->assertSuccessful();

        $this->assertSame(OrderStatus::Cancelled, $unpaid->refresh()->status);
        $this->assertSame(OrderStatus::Processing, $paid->refresh()->status);
        $this->assertSame(3, $product->defaultVariant()->fresh()->available());
    }

    public function test_zero_turns_it_off(): void
    {
        app(Settings::class)->set('sales', ['cancel_unpaid_after_hours' => 0]);
        $order = $this->order(Product::factory()->active()->create(['stock' => 5]));

        $this->travel(1000)->hours();
        $this->artisan('pnshop:orders:cancel-unpaid')->expectsOutputToContain('off')->assertSuccessful();

        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);
    }

    public function test_shipped_orders_waiting_for_cash_on_delivery_are_never_cancelled(): void
    {
        $product = Product::factory()->active()->create(['stock' => 5]);
        $order = $this->order($product);
        app(ShipmentService::class)->ship($order);
        $onHand = (int) $product->defaultVariant()->stockLevels()->sum('on_hand');

        $this->travel(1000)->hours();
        $this->artisan('pnshop:orders:cancel-unpaid')->expectsOutputToContain('Cancelled 0')->assertSuccessful();

        $order->refresh();
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(FulfillmentStatus::Fulfilled, $order->fulfillment_status);
        $this->assertSame($onHand, (int) $product->defaultVariant()->stockLevels()->sum('on_hand'), 'Shipped stock must not come back.');
    }

    public function test_an_order_paid_after_it_was_selected_is_not_cancelled(): void
    {
        $order = $this->order(Product::factory()->active()->create(['stock' => 5]));
        $workflow = app(OrderWorkflow::class);

        // Paid between the job's query and its lock: the check under the lock sees it.
        $workflow->transition($order->fresh(), PaymentStatus::Paid);
        $workflow->transition($order, OrderStatus::Cancelled, when: fn (Order $locked) => $locked->payment_status === PaymentStatus::Unpaid);

        $this->assertSame(OrderStatus::Processing, $order->refresh()->status);
    }

    public function test_orders_with_nothing_to_pay_are_paid_when_placed(): void
    {
        $order = $this->order(Product::factory()->active()->create(['stock' => 5, 'price' => '0.00', 'sale_price' => null]));

        $this->assertTrue($order->grandTotal()->isZero());
        $this->assertSame(PaymentStatus::Paid, $order->refresh()->payment_status);
        $this->assertSame(OrderStatus::Processing, $order->status);

        $this->travel(1000)->hours();
        $this->artisan('pnshop:orders:cancel-unpaid')->expectsOutputToContain('Cancelled 0')->assertSuccessful();
    }
}
