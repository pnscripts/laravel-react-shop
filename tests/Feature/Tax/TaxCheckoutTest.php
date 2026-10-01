<?php

namespace Tests\Feature\Tax;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Catalog\Models\Product;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Sales\Models\Order;
use PnShop\Settings\Settings;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\Models\ShippingZone;
use PnShop\Tax\Models\TaxClass;
use PnShop\Tax\Models\TaxZone;
use Tests\TestCase;

class TaxCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private PaymentMethod $payment;

    private ShippingMethod $courier;

    protected function setUp(): void
    {
        parent::setUp();

        $standard = TaxClass::query()->create(['name' => 'Standard', 'is_default' => true]);
        TaxZone::query()->create(['name' => 'Bulgaria', 'countries' => ['BG']])
            ->rates()->create(['tax_class_id' => $standard->id, 'name' => 'VAT 20%', 'rate' => 20]);

        $this->payment = PaymentMethod::factory()->create(['gateway' => 'cash_on_delivery']);
        $this->courier = ShippingMethod::factory()->for(ShippingZone::factory()->create(), 'zone')->create(['name' => 'Courier', 'settings' => ['cost' => '6.00']]);

        $product = Product::factory()->active()->create(['price' => '60.00', 'sale_price' => null, 'stock' => 10]);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);
    }

    public function test_gross_prices_show_the_included_tax_and_keep_the_total(): void
    {
        app(Settings::class)->set('tax', ['prices_include_tax' => true, 'store_country' => 'BG']);

        $this->get(route('cart.index'))->assertInertia(fn ($page) => $page
            ->where('cart.totals.lines.0.label', 'VAT 20%')
            ->where('cart.totals.lines.0.amount.amount', '20.00')
            ->where('cart.totals.lines.0.included', true)
            ->where('cart.totals.total.amount', '120.00')
        );

        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id, ['shipping_method_id' => $this->courier->id]))->assertSessionMissing('error');

        $order = Order::query()->sole();
        $this->assertSame('126.00', (string) $order->total->getAmount());
        // 20% of 120 gross goods + 20% of 6 gross shipping.
        $this->assertSame(['code' => 'tax', 'label' => 'VAT 20%', 'amount' => 2100, 'included' => true], $order->totals[1]);
        $this->assertSame('20.00', (string) $order->items->sole()->tax_amount->getAmount());
    }

    public function test_net_prices_add_the_tax_for_the_customers_country(): void
    {
        app(Settings::class)->set('tax', ['prices_include_tax' => false]);

        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id, ['shipping_method_id' => $this->courier->id]))->assertSessionMissing('error');

        $order = Order::query()->sole();
        $this->assertSame('151.20', (string) $order->total->getAmount());
        $this->assertSame('24.00', (string) $order->items->sole()->tax_amount->getAmount());

        // A country without a tax zone pays no tax.
        $product = Product::factory()->active()->create(['price' => '60.00', 'sale_price' => null, 'stock' => 10]);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);

        $this->postJson(route('checkout.quote'), ['country_code' => 'DE', 'shipping_method_id' => $this->courier->id])
            ->assertJsonPath('totals.total.amount', '126.00')
            ->assertJsonCount(1, 'totals.lines');
    }

    public function test_shipping_methods_can_be_zero_rated(): void
    {
        app(Settings::class)->set('tax', ['prices_include_tax' => false]);
        $zero = TaxClass::query()->create(['name' => 'Zero']);
        $this->courier->update(['tax_class_id' => $zero->id]);

        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id, ['shipping_method_id' => $this->courier->id]));

        $this->assertSame('150.00', (string) Order::query()->sole()->total->getAmount());
    }
}
