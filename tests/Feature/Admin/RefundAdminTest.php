<?php

namespace Tests\Feature\Admin;

use Livewire\Livewire;
use PnShop\Catalog\Models\Product;
use PnShop\Payment\Filament\RelationManagers\RefundsRelationManager;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Sales\Filament\Resources\Orders\Pages\ViewOrder;
use PnShop\Sales\Models\Order;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\PaymentStatus;

class RefundAdminTest extends AdminTestCase
{
    public function test_staff_refund_from_the_order_page(): void
    {
        $product = Product::factory()->active()->create(['price' => '10.00', 'sale_price' => null, 'stock' => 5]);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->post(route('checkout.store'), $this->checkoutData(PaymentMethod::factory()->create(['gateway' => 'cash_on_delivery'])->id));
        $order = Order::query()->sole();

        $this->actingAsAdministrator();

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])->assertActionHidden('refund');

        app(OrderWorkflow::class)->transition($order, PaymentStatus::Paid);
        $line = $order->items->sole();

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('refund', ['quantities' => [$line->id => 1], 'extra' => '2.50', 'restock' => true, 'reason' => 'Broken on arrival'])
            ->assertHasNoActionErrors()
            ->assertNotified('Refunded $12.50.');

        $this->assertSame(PaymentStatus::PartiallyRefunded, $order->fresh()->payment_status);

        Livewire::test(RefundsRelationManager::class, ['ownerRecord' => $order->fresh(), 'pageClass' => ViewOrder::class])
            ->assertSee('$12.50')
            ->assertSee('Broken on arrival');
    }
}
