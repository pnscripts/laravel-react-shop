<?php

namespace Tests\Feature\Admin;

use Livewire\Livewire;
use PnShop\Catalog\Models\Product;
use PnShop\Sales\Filament\Resources\Orders\Pages\ListOrders;
use PnShop\Sales\Filament\Resources\Orders\Pages\ViewOrder;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Models\OrderStatus;
use PnShop\Sales\Models\PaymentMethod;

class OrderResourceTest extends AdminTestCase
{
    private Order $order;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        OrderStatus::factory()->create(['name' => 'pending']);
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
        $cancelled = OrderStatus::factory()->create(['name' => 'cancelled']);

        Livewire::test(ViewOrder::class, ['record' => $this->order->getRouteKey()])
            ->callAction('changeStatus', ['order_status_id' => $cancelled->id])
            ->assertHasNoActionErrors()
            ->assertSee('cancelled');

        $this->assertSame($cancelled->id, $this->order->fresh()->order_status_id);
        $this->assertSame(4, $this->product->fresh()->stock);
    }

    public function test_viewers_cannot_change_status(): void
    {
        $this->actingAsStaff(['sales.orders.view']);

        Livewire::test(ViewOrder::class, ['record' => $this->order->getRouteKey()])
            ->assertActionHidden('changeStatus');
    }
}
