<?php

namespace Tests\Feature\Admin;

use Livewire\Livewire;
use PnShop\Catalog\Models\Product;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Sales\Filament\Resources\Orders\Pages\ViewOrder;
use PnShop\Sales\Models\Order;
use PnShop\Settings\Settings;

class InvoiceAdminTest extends AdminTestCase
{
    public function test_staff_issue_and_open_an_invoice(): void
    {
        app(Settings::class)->set('sales', ['invoice_on' => 'manual']);
        $product = Product::factory()->active()->create(['stock' => 3]);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);
        $this->post(route('checkout.store'), $this->checkoutData(PaymentMethod::factory()->create(['gateway' => 'cash_on_delivery'])->id));
        $order = Order::query()->sole();

        $this->actingAsAdministrator();

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->assertActionHidden('viewInvoice')
            ->callAction('issueInvoice')
            ->assertHasNoActionErrors()
            ->assertActionHidden('issueInvoice')
            ->assertActionVisible('viewInvoice');

        $invoice = $order->fresh()->invoice;
        $this->assertSame('admin_user', $order->history()->where('note', "Invoice {$invoice->number} issued.")->sole()->actor_type);

        $url = Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])->instance()->getAction('viewInvoice')?->getUrl();
        $this->assertNotNull($url);

        // Staff open it with the signed link even without a storefront session.
        $this->flushSession();
        $this->get($url)->assertOk()->assertSee($invoice->number);
    }
}
