<?php

namespace Tests\Feature\Orders;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PnShop\Acl\Models\AdminUser;
use PnShop\Catalog\Models\Product;
use PnShop\Sales\Events\OrderPlaced;
use PnShop\Sales\Events\OrderStateChanged;
use PnShop\Sales\Exceptions\InvalidOrderTransition;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Models\PaymentMethod;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\FulfillmentStatus;
use PnShop\Sales\States\OrderStatus;
use PnShop\Sales\States\PaymentStatus;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private OrderWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workflow = app(OrderWorkflow::class);
    }

    public function test_checkout_creates_a_numbered_pending_order_with_a_history(): void
    {
        Event::fake([OrderPlaced::class]);

        $order = $this->placeOrder();

        $this->assertMatchesRegularExpression('/^ORD-\d{6}$/', (string) $order->number);
        $this->assertSame([OrderStatus::Pending, PaymentStatus::Unpaid, FulfillmentStatus::Unfulfilled], [$order->status, $order->payment_status, $order->fulfillment_status]);
        $this->assertSame('en', $order->locale);
        $this->assertSame(['field' => 'status', 'from' => null, 'to' => 'pending', 'note' => 'Order placed'], $order->history()->sole()->only(['field', 'from', 'to', 'note']));
        Event::assertDispatched(OrderPlaced::class, fn (OrderPlaced $event) => $event->order->is($order));
    }

    public function test_transitions_are_recorded_with_their_actor_and_dispatched(): void
    {
        Event::fake([OrderStateChanged::class]);
        $order = $this->placeOrder();
        $admin = AdminUser::factory()->create();

        $this->workflow->transition($order, OrderStatus::Processing, $admin, 'Packing');

        $this->assertDatabaseHas('order_history', [
            'order_id' => $order->id, 'field' => 'status', 'from' => 'pending', 'to' => 'processing',
            'note' => 'Packing', 'actor_type' => 'admin_user', 'actor_id' => $admin->id,
        ]);
        Event::assertDispatched(OrderStateChanged::class, fn (OrderStateChanged $event) => $event->from === OrderStatus::Pending && $event->to === OrderStatus::Processing && $event->note === 'Packing');
    }

    public function test_a_transition_the_state_machine_does_not_allow_is_refused(): void
    {
        $order = $this->placeOrder();

        $this->expectException(InvalidOrderTransition::class);

        $this->workflow->transition($order, PaymentStatus::Refunded);
    }

    public function test_repeating_the_current_state_changes_nothing(): void
    {
        $order = $this->placeOrder();

        $this->workflow->transition($order, OrderStatus::Cancelled);
        $this->workflow->transition($order, OrderStatus::Cancelled);

        $this->assertSame(1, $order->history()->where('to', 'cancelled')->count());
    }

    public function test_paying_a_pending_order_starts_processing_it(): void
    {
        $order = $this->placeOrder();

        $this->workflow->transition($order, PaymentStatus::Paid);

        $this->assertSame(OrderStatus::Processing, $order->status);
        $this->assertSame(['payment_status', 'status'], $order->history()->where('from', '!=', null)->pluck('field')->all());
    }

    public function test_cancelled_orders_cannot_ship_until_reopened(): void
    {
        $order = $this->placeOrder();
        $this->workflow->transition($order, OrderStatus::Cancelled);

        try {
            $this->workflow->transition($order, FulfillmentStatus::Fulfilled);
            $this->fail('A cancelled order should not ship.');
        } catch (InvalidOrderTransition) {
            //
        }

        // Refunding a cancelled, paid order stays possible.
        $this->workflow->transition($order, PaymentStatus::Paid);
        $this->workflow->transition($order, PaymentStatus::Refunded);
        $this->assertSame(PaymentStatus::Refunded, $order->payment_status);
    }

    public function test_notes_are_added_to_the_history(): void
    {
        $order = $this->placeOrder();

        $this->workflow->addNote($order, 'Customer called about delivery time.');

        $this->assertSame('Customer called about delivery time.', $order->history()->where('field', 'note')->sole()->note);
    }

    public function test_storefront_shows_the_number_and_translated_states(): void
    {
        $order = $this->placeOrder();
        $this->workflow->transition($order, PaymentStatus::Paid);

        $this->get(route('orders.show', $order))->assertInertia(fn ($page) => $page
            ->where('order.number', $order->number)
            ->where('order.status', 'Processing')
            ->where('order.payment_status', 'Paid')
            ->where('order.fulfillment_status', 'Not shipped')
        );

        $this->get('/bg/orders/'.$order->id)->assertInertia(fn ($page) => $page->where('order.payment_status', 'Платена'));
    }

    private function placeOrder(): Order
    {
        $payment = PaymentMethod::factory()->create(['is_active' => true]);
        $product = Product::factory()->active()->create(['stock' => 5]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);
        $this->post(route('checkout.store'), $this->checkoutData($payment->id))->assertSessionHasNoErrors();

        return Order::query()->latest('id')->firstOrFail();
    }
}
