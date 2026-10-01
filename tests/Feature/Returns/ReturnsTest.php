<?php

namespace Tests\Feature\Returns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PnShop\Acl\Models\AdminUser;
use PnShop\Catalog\Models\Product;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Returns\Filament\Resources\Returns\Pages\ViewReturnRequest;
use PnShop\Returns\Filament\Resources\Returns\ReturnRequestResource;
use PnShop\Returns\Models\ReturnRequest;
use PnShop\Returns\Notifications\ReturnUpdated;
use PnShop\Returns\ReturnReason;
use PnShop\Returns\ReturnService;
use PnShop\Returns\ReturnStatus;
use PnShop\Sales\Exceptions\OrderException;
use PnShop\Sales\Models\Order;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\PaymentStatus;
use PnShop\Settings\Settings;
use PnShop\Shipping\ShipmentService;
use Tests\TestCase;

class ReturnsTest extends TestCase
{
    use RefreshDatabase;

    private Product $mug;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mug = Product::factory()->active()->create(['price' => '10.00', 'sale_price' => null, 'stock' => 10]);
    }

    private function shippedOrder(int $quantity = 3, bool $ship = true): Order
    {
        $this->post(route('cart.store'), ['product_id' => $this->mug->id, 'quantity' => $quantity]);
        $this->post(route('checkout.store'), $this->checkoutData(PaymentMethod::factory()->create(['gateway' => 'bank_transfer'])->id))->assertSessionMissing('error');

        $order = Order::query()->latest('id')->firstOrFail();
        app(OrderWorkflow::class)->transition($order, PaymentStatus::Paid);

        if ($ship) {
            app(ShipmentService::class)->ship($order);
        }

        return $order->fresh(['items']);
    }

    private function onHand(): int
    {
        return (int) $this->mug->defaultVariant()->stockLevels()->sum('on_hand');
    }

    public function test_the_customer_requests_a_return_from_the_order_page(): void
    {
        Notification::fake();
        $order = $this->shippedOrder();
        $item = $order->items->sole();

        // The browser that placed the order sees the form.
        $this->get(route('orders.show', $order))->assertInertia(fn ($page) => $page
            ->where('returnable.allowed', true)
            ->where('returnable.items.0.max', 3)
        );

        $this->post(route('orders.returns.store', $order), ['items' => [$item->id => 5], 'reason' => 'damaged'])->assertSessionHasErrors('items');

        $this->post(route('orders.returns.store', $order), ['items' => [$item->id => 2], 'reason' => 'damaged', 'note' => 'Cracked handle'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $return = ReturnRequest::query()->sole();
        $this->assertSame('RMA-000001', $return->number);
        $this->assertSame(ReturnStatus::Requested, $return->status);
        $this->assertSame('Cracked handle', $return->customer_note);
        Notification::assertSentOnDemand(ReturnUpdated::class);

        // Units in an open return cannot be requested twice.
        $this->get(route('orders.show', $order))->assertInertia(fn ($page) => $page->where('returnable.items.0.max', 1)->where('returns.0.number', 'RMA-000001'));

        // Strangers cannot.
        $this->flushSession();
        $this->post(route('orders.returns.store', $order), ['items' => [$item->id => 1], 'reason' => 'other'])->assertForbidden();
    }

    public function test_only_shipped_orders_within_the_window(): void
    {
        $returns = app(ReturnService::class);

        $unshipped = $this->shippedOrder(ship: false);
        $this->assertFalse($returns->eligibility($unshipped)['allowed']);

        $shipped = $this->shippedOrder();
        $this->assertTrue($returns->eligibility($shipped)['allowed']);

        $this->travel(15)->days();
        $this->assertSame('The return period for this order has ended.', $returns->eligibility($shipped)['reason']);

        app(Settings::class)->set('returns', ['window_days' => 30]);
        $this->assertTrue($returns->eligibility($shipped)['allowed']);

        app(Settings::class)->set('returns', ['enabled' => false]);
        $this->assertFalse($returns->eligibility($shipped)['allowed']);
    }

    public function test_staff_approve_receive_with_restock_and_refund(): void
    {
        $order = $this->shippedOrder();
        $item = $order->items->sole();
        $returns = app(ReturnService::class);
        $admin = AdminUser::factory()->administrator()->create();

        $return = $returns->request($order, [$item->id => 2], ReturnReason::NoLongerNeeded);
        $stockAfterShipping = $this->onHand();

        $returns->approve($return, 'Send it to our warehouse.', $admin);
        $this->assertSame('Send it to our warehouse.', $return->refresh()->staff_note);

        // Only one arrived; it goes back on the shelf.
        $returns->receive($return, [$return->lines->sole()->id => 1], restock: true, actor: $admin);
        $this->assertSame($stockAfterShipping + 1, $this->onHand());
        $this->assertDatabaseHas('stock_movements', ['reason' => 'return', 'quantity' => 1, 'note' => $return->number]);

        $returns->refund($return->refresh(), $admin);

        $return->refresh();
        $this->assertSame(ReturnStatus::Refunded, $return->status);
        $this->assertSame('10.00', (string) $return->refund?->amount->getAmount());
        $this->assertSame(1, $item->refresh()->quantity_refunded);
        // Refunded units are not restocked a second time.
        $this->assertSame($stockAfterShipping + 1, $this->onHand());

        $returns->close($return, null, $admin);
        $this->assertSame(ReturnStatus::Closed, $return->refresh()->status);

        // Two units can still be returned: three shipped, one refunded.
        $this->assertSame([$item->id => 2], $returns->eligibility($order->refresh())['items']);
    }

    public function test_transitions_are_enforced(): void
    {
        $order = $this->shippedOrder();
        $return = app(ReturnService::class)->request($order, [$order->items->sole()->id => 1], ReturnReason::Other);

        $this->expectException(OrderException::class);
        app(ReturnService::class)->refund($return);
    }

    public function test_admin_handles_a_return(): void
    {
        $order = $this->shippedOrder();
        $return = app(ReturnService::class)->request($order, [$order->items->sole()->id => 1], ReturnReason::WrongItem);

        $this->actingAs(AdminUser::factory()->withPermissions(['sales.returns.manage'])->create(), 'admin');

        $this->get(ReturnRequestResource::getUrl('index'))->assertOk()->assertSee($return->number);
        $this->get(ReturnRequestResource::getUrl('view', ['record' => $return]))->assertOk();

        Livewire::test(ViewReturnRequest::class, ['record' => $return->id])
            ->callAction('approve', data: ['note' => 'Use the prepaid label.'])
            ->assertHasNoActionErrors();

        $this->assertSame('Use the prepaid label.', $return->refresh()->staff_note);

        Livewire::test(ViewReturnRequest::class, ['record' => $return->id])
            ->callAction('receive', data: ['received' => [$return->lines->sole()->id => 1], 'restock' => false])
            ->assertHasNoActionErrors();

        $this->assertSame(ReturnStatus::Received, $return->refresh()->status);
        $this->assertFalse($return->restocked);
    }

    public function test_others_cannot_open_returns(): void
    {
        $this->actingAs(AdminUser::factory()->withPermissions(['sales.orders.view'])->create(), 'admin');

        $this->get(ReturnRequestResource::getUrl('index'))->assertForbidden();
    }
}
