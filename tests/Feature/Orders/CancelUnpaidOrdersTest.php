<?php

namespace Tests\Feature\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Catalog\Models\Product;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Sales\Models\Order;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\OrderStatus;
use PnShop\Sales\States\PaymentStatus;
use PnShop\Settings\Settings;
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
}
