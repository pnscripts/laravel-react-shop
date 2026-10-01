<?php

namespace Tests\Feature\Orders;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PnShop\Catalog\Models\Product;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Sales\Invoices\InvoiceService;
use PnShop\Sales\Models\Invoice;
use PnShop\Sales\Models\Order;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\PaymentStatus;
use PnShop\Settings\Settings;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_paying_an_order_issues_a_numbered_invoice(): void
    {
        app(Settings::class)->set('store', ['name' => 'PN Demo', 'address' => 'Main St 1, Sofia']);
        app(Settings::class)->set('sales', ['invoice_tax_number' => 'BG123456789']);

        $order = $this->placeOrder();
        $this->assertNull($order->invoice);

        app(OrderWorkflow::class)->transition($order, PaymentStatus::Paid);

        $invoice = $order->fresh()->invoice;
        $this->assertSame('INV-000001', $invoice->number);
        $this->assertSame('PN Demo', $invoice->seller['name']);
        $this->assertSame('BG123456789', $invoice->seller['tax_number']);
        $this->assertSame('Jane Doe', $invoice->buyer['name']);
        $this->assertSame(2, $invoice->lines[0]['quantity']);
        $this->assertSame(2400, $invoice->totals['total']);
        $this->assertTrue($order->history()->where('note', 'Invoice INV-000001 issued.')->exists());
    }

    public function test_numbers_are_sequential_and_one_per_order(): void
    {
        app(Settings::class)->set('sales', ['invoice_on' => 'manual', 'invoice_prefix' => 'F', 'invoice_digits' => 4]);
        $first = $this->placeOrder();
        $second = $this->placeOrder();
        $invoices = app(InvoiceService::class);

        $this->assertSame('F0001', $invoices->issue($second)->number);
        $this->assertSame('F0002', $invoices->issue($first)->number);
        $this->assertSame('F0001', $invoices->issue($second)->number);

        app(OrderWorkflow::class)->transition($first, PaymentStatus::Paid);
        $this->assertSame(2, Invoice::query()->count());
    }

    public function test_invoices_can_be_issued_when_the_order_is_placed(): void
    {
        app(Settings::class)->set('sales', ['invoice_on' => 'placed']);

        $this->assertNotNull($this->placeOrder()->invoice);
    }

    public function test_an_issued_invoice_does_not_change_with_the_store(): void
    {
        app(Settings::class)->set('sales', ['invoice_on' => 'placed']);
        app(Settings::class)->set('store', ['name' => 'Old name']);
        $order = $this->placeOrder();

        app(Settings::class)->set('store', ['name' => 'New name']);

        $this->get(route('invoices.show', $order->invoice))->assertOk()->assertSee('Old name')->assertDontSee('New name');
    }

    public function test_only_the_customer_or_staff_can_open_an_invoice(): void
    {
        app(Settings::class)->set('sales', ['invoice_on' => 'placed']);
        $customer = User::factory()->create();
        $order = $this->placeOrder($customer);
        $invoice = $order->invoice;

        // The browser that placed the order, and the account that owns it.
        $this->get(route('invoices.show', $invoice))->assertOk()->assertSee($invoice->number);
        $this->flushSession();
        $this->actingAs($customer)->get(route('invoices.show', $invoice))->assertOk();

        $this->actingAs(User::factory()->create())->get(route('invoices.show', $invoice))->assertForbidden();
        $this->get(URL::temporarySignedRoute('invoices.show', now()->addMinutes(5), ['invoice' => $invoice]))->assertOk();
    }

    public function test_invoices_are_printed_in_the_language_of_the_order(): void
    {
        app(Settings::class)->set('sales', ['invoice_on' => 'placed']);
        $product = Product::factory()->active()->create(['price' => '12.00', 'sale_price' => null, 'stock' => 5]);
        $this->post('/bg/cart', ['product_id' => $product->id, 'quantity' => 1]);
        $this->post('/bg/checkout', $this->checkoutData(PaymentMethod::factory()->create(['gateway' => 'cash_on_delivery'])->id));

        $invoice = Order::query()->sole()->invoice;

        $this->get(route('invoices.show', $invoice))
            ->assertSee('Фактура '.$invoice->number)
            ->assertSee('12,00', false);
    }

    private function placeOrder(?User $customer = null): Order
    {
        $product = Product::factory()->active()->create(['price' => '12.00', 'sale_price' => null, 'stock' => 5]);
        $method = PaymentMethod::factory()->create(['gateway' => 'cash_on_delivery']);

        $request = $customer ? $this->actingAs($customer) : $this;
        $request->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);
        $request->post(route('checkout.store'), $this->checkoutData($method->id))->assertSessionMissing('error');

        return Order::query()->latest('id')->firstOrFail();
    }
}
