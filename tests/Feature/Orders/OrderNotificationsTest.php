<?php

namespace Tests\Feature\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use PnShop\Catalog\Models\Product;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Payment\RefundService;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Notifications\NewOrderForStaff;
use PnShop\Sales\Notifications\OrderCancelled;
use PnShop\Sales\Notifications\OrderConfirmation;
use PnShop\Sales\Notifications\OrderRefunded;
use PnShop\Sales\Notifications\OrderShipped;
use PnShop\Sales\OrderLinks;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\OrderStatus;
use PnShop\Sales\States\PaymentStatus;
use PnShop\Settings\Settings;
use PnShop\Shipping\ShipmentService;
use Tests\TestCase;

class OrderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        app(Settings::class)->set('store', ['name' => 'PN Demo', 'email' => 'shop@example.com']);
    }

    public function test_customer_and_store_hear_about_a_new_order(): void
    {
        $order = $this->placeOrder();

        Notification::assertSentOnDemand(OrderConfirmation::class, fn ($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'jane@example.com'
            && $notification->order->is($order));
        Notification::assertSentOnDemand(NewOrderForStaff::class, fn ($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'shop@example.com');

        $mail = (new OrderConfirmation($order))->toMail(new AnonymousNotifiable);
        $this->assertSame('Order '.$order->number.' confirmed', $mail->subject);
        $this->assertStringContainsString('Please transfer', implode("\n", $mail->introLines));
        $this->assertStringContainsString('signature=', (string) $mail->actionUrl);
    }

    public function test_shipping_cancelling_and_refunding_are_announced(): void
    {
        $order = $this->placeOrder();

        app(ShipmentService::class)->ship($order, [], 'TRK1');
        Notification::assertSentOnDemand(OrderShipped::class, fn (OrderShipped $notification) => $notification->shipment->tracking_number === 'TRK1');

        app(OrderWorkflow::class)->transition($order, PaymentStatus::Paid);
        app(RefundService::class)->refund($order->fresh(), [$order->items->sole()->id => 1]);
        Notification::assertSentOnDemand(OrderRefunded::class);

        $second = $this->placeOrder();
        app(OrderWorkflow::class)->transition($second, OrderStatus::Cancelled);
        Notification::assertSentOnDemand(OrderCancelled::class, fn (OrderCancelled $notification) => $notification->order->is($second));
    }

    public function test_emails_can_be_switched_off(): void
    {
        app(Settings::class)->set('notifications', ['order_confirmation' => false, 'staff_new_order' => false]);

        $this->placeOrder();

        Notification::assertNothingSent();
    }

    public function test_emails_use_the_language_of_the_order(): void
    {
        $product = Product::factory()->active()->create(['stock' => 5]);
        $this->post('/bg/cart', ['product_id' => $product->id, 'quantity' => 1]);
        $this->post('/bg/checkout', $this->checkoutData($this->method()->id));

        Notification::assertSentOnDemand(OrderConfirmation::class, fn (OrderConfirmation $notification) => $notification->locale === 'bg');

        $this->assertStringContainsString('/bg/orders/', OrderLinks::signedShow(Order::query()->sole()));
    }

    public function test_the_signed_link_opens_the_order_in_any_browser(): void
    {
        $order = $this->placeOrder();
        $link = OrderLinks::signedShow($order);

        $this->flushSession();
        $this->get(route('orders.show', $order))->assertForbidden();
        $this->get($link)->assertOk();
        // The browser now remembers the order.
        $this->get(route('orders.show', $order))->assertOk();

        $this->flushSession();
        $this->get(str_replace('signature=', 'signature=0', $link))->assertForbidden();
    }

    private function placeOrder(): Order
    {
        $product = Product::factory()->active()->create(['stock' => 5]);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);
        $this->post(route('checkout.store'), $this->checkoutData($this->method()->id))->assertSessionMissing('error');

        return Order::query()->latest('id')->firstOrFail();
    }

    private function method(): PaymentMethod
    {
        return PaymentMethod::factory()->create(['gateway' => 'bank_transfer']);
    }
}
