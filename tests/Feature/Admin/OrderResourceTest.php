<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\Product;
use Livewire\Livewire;
use PnShop\Sales\Filament\Resources\Orders\Pages\ListOrders;
use PnShop\Sales\Filament\Resources\Orders\Pages\ViewOrder;

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
        $this->post(route('checkout.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '0888123456',
            'address' => '123 Main St',
            'payment_method_id' => $payment->id,
        ]);

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
