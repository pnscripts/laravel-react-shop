<?php

namespace Tests\Feature\Cart;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Cart\Totals\CartCalculator;
use PnShop\Cart\Totals\CartTotals;
use PnShop\Cart\Totals\TotalLine;
use PnShop\Catalog\Models\Product;
use PnShop\Foundation\Extension\PipelineRegistry;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Models\OrderAddress;
use PnShop\Sales\Models\PaymentMethod;
use Tests\TestCase;

class CheckoutAddressesAndTotalsTest extends TestCase
{
    use RefreshDatabase;

    private PaymentMethod $payment;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = PaymentMethod::factory()->create(['is_active' => true]);
        $this->product = Product::factory()->active()->create(['price' => '20.00', 'sale_price' => null, 'stock' => 10]);
    }

    public function test_order_keeps_copies_of_the_shipping_and_billing_addresses(): void
    {
        $this->post(route('cart.store'), ['product_id' => $this->product->id, 'quantity' => 1]);

        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id, [
            'billing_same_as_shipping' => false,
            'billing' => ['first_name' => 'Acme', 'last_name' => 'Ltd', 'company' => 'Acme Ltd', 'line1' => '1 Office Rd', 'city' => 'Plovdiv', 'country_code' => 'BG'],
        ]))->assertRedirect();

        $order = Order::query()->sole();

        $this->assertSame('Jane Doe', $order->name);
        $this->assertSame('0888123456', $order->phone);
        $this->assertSame('Sofia', $order->shippingAddress->city);
        $this->assertSame('Acme Ltd', $order->billingAddress->company);
        $this->assertSame('Plovdiv', $order->billingAddress->city);
    }

    public function test_billing_defaults_to_the_shipping_address(): void
    {
        $this->post(route('cart.store'), ['product_id' => $this->product->id, 'quantity' => 1]);
        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id, ['billing' => ['city' => 'ignored']]));

        $order = Order::query()->sole();

        $this->assertSame('Sofia', $order->billingAddress->city);
        $this->assertSame(2, OrderAddress::query()->count());
    }

    public function test_shipping_address_and_phone_are_required(): void
    {
        $this->post(route('cart.store'), ['product_id' => $this->product->id, 'quantity' => 1]);

        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id, ['shipping' => ['line1' => '', 'phone' => '', 'country_code' => 'XX']]))
            ->assertSessionHasErrors(['shipping.line1', 'shipping.phone', 'shipping.country_code']);

        $this->assertSame(0, Order::query()->count());
    }

    public function test_customers_can_save_the_address_for_next_time(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.store'), ['product_id' => $this->product->id, 'quantity' => 1]);
        $this->actingAs($user)->post(route('checkout.store'), $this->checkoutData($this->payment->id, ['save_address' => true]));

        $address = $user->addresses()->sole();
        $this->assertSame('123 Main St', $address->line1);
        $this->assertTrue($address->is_default_shipping);

        // The same address is not saved twice.
        $this->actingAs($user)->post(route('cart.store'), ['product_id' => $this->product->id, 'quantity' => 1]);
        $this->actingAs($user)->post(route('checkout.store'), $this->checkoutData($this->payment->id, ['save_address' => true]));

        $this->assertSame(1, $user->addresses()->count());

        $this->actingAs($user)->post(route('cart.store'), ['product_id' => $this->product->id, 'quantity' => 1]);
        $this->actingAs($user)->get(route('checkout.create'))
            ->assertInertia(fn ($page) => $page->where('savedAddresses.0.line1', '123 Main St'));
    }

    public function test_totals_pipeline_stages_change_the_cart_and_the_order(): void
    {
        app(PipelineRegistry::class)->stage(CartCalculator::PIPELINE, function (CartTotals $totals, \Closure $next) {
            $totals->add(new TotalLine('shipping', 'Courier', Money::of('5.00', $totals->currency())));
            $totals->add(new TotalLine('discount', 'Welcome', Money::of('-3.00', $totals->currency())));
            $totals->add(new TotalLine('tax', 'VAT 20%', Money::of('7.00', $totals->currency()), included: true));

            return $next($totals);
        });

        $this->post(route('cart.store'), ['product_id' => $this->product->id, 'quantity' => 2]);

        $this->get(route('cart.index'))->assertInertia(fn ($page) => $page
            ->where('cart.totals.subtotal.amount', '40.00')
            ->where('cart.totals.lines.0.label', 'Courier')
            ->where('cart.totals.total.amount', '42.00')
        );

        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id));

        $order = Order::query()->sole();
        $this->assertSame('40.00', (string) $order->subtotal->getAmount());
        $this->assertSame('42.00', (string) $order->total->getAmount());
        $this->assertSame(['code' => 'shipping', 'label' => 'Courier', 'amount' => 500, 'included' => false], $order->totals[0]);

        $this->get(route('orders.show', $order))->assertInertia(fn ($page) => $page
            ->where('order.totals.total.amount', '42.00')
            ->where('order.totals.lines.2.included', true)
            ->where('order.shipping_address.0', 'Jane Doe')
        );
    }

    public function test_the_total_never_goes_below_zero(): void
    {
        app(PipelineRegistry::class)->stage(CartCalculator::PIPELINE, fn (CartTotals $totals, \Closure $next) => $next(
            $totals->add(new TotalLine('discount', 'Too generous', Money::of('-500', $totals->currency()))),
        ));

        $this->post(route('cart.store'), ['product_id' => $this->product->id, 'quantity' => 1]);

        $this->get(route('cart.index'))->assertInertia(fn ($page) => $page->where('cart.totals.total.amount', '0.00'));
    }

    public function test_orders_placed_before_structured_addresses_still_display(): void
    {
        $order = Order::factory()->create(['name' => 'Old Customer', 'address' => 'Somewhere 1, Sofia', 'currency' => 'USD']);

        $this->assertSame(['Old Customer', 'Somewhere 1, Sofia'], $order->shippingLines());
        $this->assertSame('0.00', $order->presentTotals()['total']['amount']);
    }
}
