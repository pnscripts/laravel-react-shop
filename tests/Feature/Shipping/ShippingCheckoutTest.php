<?php

namespace Tests\Feature\Shipping;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Catalog\Models\Product;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Sales\Models\Order;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\Models\ShippingZone;
use Tests\TestCase;

class ShippingCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private PaymentMethod $payment;

    private ShippingMethod $courier;

    private ShippingMethod $pickup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = PaymentMethod::factory()->create(['gateway' => 'cash_on_delivery']);
        $zone = ShippingZone::factory()->create(['name' => 'Bulgaria', 'countries' => ['BG']]);
        $this->courier = ShippingMethod::factory()->for($zone, 'zone')->create(['name' => 'Courier', 'settings' => ['cost' => '6.00']]);
        $this->pickup = ShippingMethod::factory()->for($zone, 'zone')->create(['name' => 'Pickup', 'carrier' => 'pickup', 'settings' => [], 'description' => 'Shop, Main St 1']);

        $product = Product::factory()->active()->create(['price' => '20.00', 'sale_price' => null, 'stock' => 10]);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);
    }

    public function test_checkout_quotes_delivery_for_the_address(): void
    {
        $this->get(route('checkout.create'))->assertInertia(fn ($page) => $page->where('shippingRequired', true));

        $this->postJson(route('checkout.quote'), ['country_code' => 'BG', 'postcode' => '1000', 'shipping_method_id' => $this->courier->id])
            ->assertOk()
            ->assertJsonPath('options.0.name', 'Pickup')
            ->assertJsonPath('options.0.price.amount', '0.00')
            ->assertJsonPath('options.1.name', 'Courier')
            ->assertJsonPath('selected', $this->courier->id)
            ->assertJsonPath('totals.lines.0.label', 'Courier')
            ->assertJsonPath('totals.total.amount', '46.00');

        $this->postJson(route('checkout.quote'), ['country_code' => 'DE'])
            ->assertJsonPath('options', [])
            ->assertJsonPath('selected', null)
            ->assertJsonPath('totals.total.amount', '40.00');
    }

    public function test_the_order_stores_the_delivery_method_and_its_price(): void
    {
        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id, ['shipping_method_id' => $this->courier->id]))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('error');

        $order = Order::query()->sole();
        $this->assertSame($this->courier->id, $order->shipping_method_id);
        $this->assertSame('Courier', $order->shipping_method_name);
        $this->assertSame('46.00', (string) $order->total->getAmount());
        $this->assertSame(['code' => 'shipping', 'label' => 'Courier', 'amount' => 600, 'included' => false], $order->totals[0]);
        $this->assertSame('46.00', (string) $order->payments()->sole()->amount->getAmount());
    }

    public function test_a_delivery_method_is_required_and_must_serve_the_address(): void
    {
        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id))->assertSessionHas('error');
        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id, [
            'shipping_method_id' => $this->courier->id,
            'shipping' => ['country_code' => 'DE'],
        ]))->assertSessionHas('error');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_stores_without_shipping_methods_do_not_ask_for_one(): void
    {
        ShippingMethod::query()->delete();

        $this->get(route('checkout.create'))->assertInertia(fn ($page) => $page->where('shippingRequired', false));
        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id))->assertSessionMissing('error');

        $this->assertNull(Order::query()->sole()->shipping_method_id);
    }
}
