<?php

namespace Tests\Feature\Admin;

use Livewire\Livewire;
use PnShop\Catalog\Models\Product;
use PnShop\Sales\Filament\Resources\Orders\Pages\ListOrders;
use PnShop\Sales\Filament\Resources\Orders\Pages\ViewOrder;
use PnShop\Sales\Filament\Resources\Orders\RelationManagers\HistoryRelationManager;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Models\PaymentMethod;
use PnShop\Sales\States\OrderStatus;
use PnShop\Sales\States\PaymentStatus;

class OrderResourceTest extends AdminTestCase
{
    private Order $order;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $payment = PaymentMethod::factory()->create(['is_active' => true]);
        $this->product = Product::factory()->active()->create(['stock' => 4, 'title' => 'Mug']);

        $this->post(route('cart.store'), ['product_id' => $this->product->id, 'quantity' => 2]);
        $this->post(route('checkout.store'), $this->checkoutData($payment->id, ['email' => 'jane@example.com']));

        $this->order = Order::query()->sole();
    }

    public function test_orders_are_listed_and_viewable(): void
    {
        $this->actingAsAdministrator();

        Livewire::test(ListOrders::class)->assertCanSeeTableRecords([$this->order]);
        Livewire::test(ViewOrder::class, ['record' => $this->order->getRouteKey()])
            ->assertSee('Jane Doe')
            ->assertSee('Mug');
    }

    public function test_cancelling_from_the_admin_returns_stock(): void
    {
        $this->actingAsAdministrator();

        Livewire::test(ViewOrder::class, ['record' => $this->order->getRouteKey()])
            ->callAction('changeStatus', ['state' => 'cancelled', 'note' => 'Customer asked'])
            ->assertHasNoActionErrors()
            ->assertSee('Cancelled');

        $this->assertSame(OrderStatus::Cancelled, $this->order->fresh()->status);
        $this->assertDatabaseHas('order_history', ['order_id' => $this->order->id, 'field' => 'status', 'from' => 'pending', 'to' => 'cancelled', 'note' => 'Customer asked', 'actor_type' => 'admin_user']);
        $this->assertSame(4, $this->product->fresh()->stock);
    }

    public function test_viewers_cannot_change_status(): void
    {
        $this->actingAsStaff(['sales.orders.view']);

        Livewire::test(ViewOrder::class, ['record' => $this->order->getRouteKey()])
            ->assertActionHidden('changeStatus');
    }

    public function test_staff_record_payments_and_see_the_history(): void
    {
        $admin = $this->actingAsAdministrator();

        Livewire::test(ViewOrder::class, ['record' => $this->order->getRouteKey()])
            ->assertActionVisible('changeFulfillment')
            ->callAction('changePayment', ['state' => 'paid'])
            ->assertHasNoActionErrors()
            ->callAction('addNote', ['note' => 'Paid by bank transfer'])
            ->assertHasNoActionErrors();

        $this->assertSame(PaymentStatus::Paid, $this->order->fresh()->payment_status);
        $this->assertSame(OrderStatus::Processing, $this->order->fresh()->status);

        Livewire::test(HistoryRelationManager::class, ['ownerRecord' => $this->order->fresh(), 'pageClass' => ViewOrder::class])
            ->assertSee('Unpaid → Paid')
            ->assertSee('Pending → Processing')
            ->assertSee('Paid by bank transfer')
            ->assertSee($admin->name.' (staff)');
    }
}
