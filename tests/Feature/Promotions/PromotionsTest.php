<?php

namespace Tests\Feature\Promotions;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Cart\Totals\CartCalculator;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Product;
use PnShop\Customer\Models\CustomerGroup;
use PnShop\Foundation\Extension\PipelineRegistry;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Payment\RefundService;
use PnShop\Promotion\Models\Coupon;
use PnShop\Promotion\Models\Promotion;
use PnShop\Promotion\Models\PromotionRedemption;
use PnShop\Sales\Models\Order;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\OrderStatus;
use PnShop\Sales\States\PaymentStatus;
use PnShop\Settings\Settings;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\Models\ShippingZone;
use PnShop\Tax\Models\TaxClass;
use PnShop\Tax\Models\TaxZone;
use Tests\TestCase;

class PromotionsTest extends TestCase
{
    use RefreshDatabase;

    private PaymentMethod $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payment = PaymentMethod::factory()->create(['gateway' => 'cash_on_delivery']);
    }

    private function product(string $price, array $attributes = []): Product
    {
        return Product::factory()->active()->create(['price' => $price, 'sale_price' => null, 'stock' => 50, ...$attributes]);
    }

    private function add(Product $product, int $quantity = 1): void
    {
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => $quantity])->assertSessionHasNoErrors();
    }

    /**
     * @param  list<array{type: string, data?: array<string, mixed>}>  $actions
     * @param  list<array{type: string, data?: array<string, mixed>}>  $conditions
     */
    private function promotion(array $actions, array $conditions = [], array $attributes = []): Promotion
    {
        return Promotion::factory()->create(['actions' => $actions, 'conditions' => $conditions, ...$attributes]);
    }

    /**
     * @return array<string, mixed>
     */
    private function cart(): array
    {
        $cart = null;
        $this->get(route('cart.index'))->assertInertia(function ($page) use (&$cart) {
            $cart = $page->toArray()['props']['cart'];

            return $page;
        });

        return $cart;
    }

    public function test_percent_off_everything_shows_a_discount_line(): void
    {
        $this->promotion([['type' => 'percent_off', 'data' => ['percent' => 10]]], attributes: ['name' => 'Autumn sale']);
        $this->add($this->product('40.00'), 2);

        $cart = $this->cart();

        $this->assertSame('discount:'.Promotion::query()->sole()->id, $cart['totals']['lines'][0]['code']);
        $this->assertSame('Autumn sale', $cart['totals']['lines'][0]['label']);
        $this->assertSame('-8.00', $cart['totals']['lines'][0]['amount']['amount']);
        $this->assertSame('72.00', $cart['totals']['total']['amount']);
    }

    public function test_conditions_must_all_hold_and_scope_limits_the_discount(): void
    {
        $shoes = Category::factory()->create(['is_active' => true]);
        $runners = Category::factory()->create(['is_active' => true, 'parent_id' => $shoes->id]);
        $shoe = $this->product('100.00', ['product_category_id' => $runners->id]);
        $sock = $this->product('10.00', ['product_category_id' => Category::factory()->create()->id]);

        $this->promotion(
            [['type' => 'percent_off', 'data' => ['percent' => 20, 'category_ids' => [$shoes->id]]]],
            [['type' => 'subtotal', 'data' => ['min' => '120']]],
        );

        $this->add($shoe);
        $this->add($sock);
        $this->assertSame('110.00', $this->cart()['totals']['total']['amount']);

        // Subtotal reaches 120: 20% off the shoe (a subcategory of Shoes) only.
        $this->add($sock);
        $this->assertSame('100.00', $this->cart()['totals']['total']['amount']);
    }

    public function test_an_unknown_condition_type_never_holds(): void
    {
        $this->promotion([['type' => 'percent_off', 'data' => ['percent' => 50]]], [['type' => 'removed_plugin_condition']]);
        $this->add($this->product('10.00'));

        $this->assertSame('10.00', $this->cart()['totals']['total']['amount']);
    }

    public function test_fixed_amount_is_spread_and_never_exceeds_the_lines(): void
    {
        $this->promotion([['type' => 'fixed_off', 'data' => ['amount' => '10']]]);
        $a = $this->product('30.00');
        $b = $this->product('10.00');
        $this->add($a);
        $this->add($b);

        $this->assertSame('30.00', $this->cart()['totals']['total']['amount']);

        Promotion::query()->update(['actions' => [['type' => 'fixed_off', 'data' => ['amount' => '500']]]]);
        $this->assertSame('0.00', $this->cart()['totals']['total']['amount']);
    }

    public function test_buy_two_get_one_free_discounts_the_cheapest(): void
    {
        $this->promotion([['type' => 'buy_x_get_y', 'data' => ['buy' => 2, 'get' => 1, 'percent' => 100]]]);
        $this->add($this->product('30.00'), 2);
        $this->add($this->product('12.00'), 1);

        $this->assertSame('-12.00', $this->cart()['totals']['lines'][0]['amount']['amount']);
    }

    public function test_promotions_stack_in_order_until_one_stops(): void
    {
        $this->promotion([['type' => 'percent_off', 'data' => ['percent' => 50]]], attributes: ['position' => 1, 'stop_further' => true]);
        $this->promotion([['type' => 'fixed_off', 'data' => ['amount' => '5']]], attributes: ['position' => 2]);
        $this->add($this->product('100.00'));

        $this->assertSame('50.00', $this->cart()['totals']['total']['amount']);

        Promotion::query()->update(['stop_further' => false]);
        // 50% first, then 5 off what is left.
        $this->assertSame('45.00', $this->cart()['totals']['total']['amount']);
    }

    public function test_coupons_unlock_promotions_and_are_validated(): void
    {
        $promotion = $this->promotion([['type' => 'percent_off', 'data' => ['percent' => 15]]], [['type' => 'subtotal', 'data' => ['min' => '50']]], ['requires_coupon' => true, 'name' => 'Welcome']);
        $promotion->coupons()->create(['code' => 'welcome15']);
        $this->add($this->product('40.00'));

        $this->assertSame('40.00', $this->cart()['totals']['total']['amount']);

        $this->post(route('cart.coupon.store'), ['code' => 'NOPE'])->assertSessionHasErrors('code');

        // A valid code that does not apply yet is kept, with the reason.
        $this->post(route('cart.coupon.store'), ['code' => ' Welcome15 '])->assertSessionHasNoErrors();
        $cart = $this->cart();
        $this->assertSame(['code' => 'WELCOME15', 'valid' => true, 'applied' => false, 'message' => 'This coupon does not apply to your cart.'], $cart['coupon']);

        $this->add($this->product('20.00'));
        $cart = $this->cart();
        $this->assertTrue($cart['coupon']['applied']);
        $this->assertSame('Welcome (WELCOME15)', $cart['totals']['lines'][0]['label']);
        $this->assertSame('51.00', $cart['totals']['total']['amount']);

        $this->delete(route('cart.coupon.destroy'));
        $this->assertNull($this->cart()['coupon']);
    }

    public function test_customer_group_condition(): void
    {
        $vip = CustomerGroup::query()->create(['code' => 'vip', 'name' => 'VIP']);
        $this->promotion([['type' => 'percent_off', 'data' => ['percent' => 10]]], [['type' => 'customer_group', 'data' => ['group_ids' => [$vip->id]]]]);
        $product = $this->product('100.00');

        $this->add($product);
        $this->assertSame('100.00', $this->cart()['totals']['total']['amount']);

        $user = User::factory()->create();
        $user->forceFill(['customer_group_id' => $vip->id])->save();
        // Signed in: the customer's own cart.
        $this->actingAs($user);
        $this->add($product);
        $this->assertSame('90.00', $this->cart()['totals']['total']['amount']);
    }

    public function test_tax_is_charged_on_the_discounted_price_and_shipping_can_be_free(): void
    {
        $standard = TaxClass::query()->create(['name' => 'Standard', 'is_default' => true]);
        TaxZone::query()->create(['name' => 'Bulgaria', 'countries' => ['BG']])
            ->rates()->create(['tax_class_id' => $standard->id, 'name' => 'VAT 20%', 'rate' => 20]);
        app(Settings::class)->set('tax', ['prices_include_tax' => true, 'store_country' => 'BG']);
        $courier = ShippingMethod::factory()->for(ShippingZone::factory()->create(['countries' => ['BG']]), 'zone')->create(['name' => 'Courier', 'settings' => ['cost' => '6.00']]);

        $this->promotion([['type' => 'percent_off', 'data' => ['percent' => 50]], ['type' => 'free_shipping']], attributes: ['name' => 'Half price']);
        $this->add($this->product('120.00'));

        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id, ['shipping_method_id' => $courier->id]))->assertSessionMissing('error');

        $order = Order::query()->sole();
        $codes = array_column($order->totals, 'amount', 'code');

        $this->assertSame('60.00', (string) $order->total->getAmount());
        $this->assertSame(-6000, $codes['discount:'.Promotion::query()->sole()->id]);
        $this->assertSame(-600, $codes['discount:'.Promotion::query()->sole()->id.':shipping']);
        // 20% included in the 60.00 actually paid; shipping is free so carries no tax.
        $this->assertSame(1000, $codes['tax']);
        $this->assertSame('60.00', (string) $order->items->sole()->discount_amount->getAmount());

        $redemption = PromotionRedemption::query()->sole();
        $this->assertSame('66.00', (string) $redemption->amount->getAmount());
        $this->assertSame(1, Promotion::query()->sole()->times_used);
    }

    public function test_usage_limits_are_enforced_at_checkout_and_released_on_cancel(): void
    {
        $promotion = $this->promotion([['type' => 'percent_off', 'data' => ['percent' => 10]]], attributes: ['usage_limit' => 1]);
        $product = $this->product('50.00');

        $this->add($product);
        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id))->assertSessionMissing('error');
        $this->assertSame(1, $promotion->refresh()->times_used);

        // Used up: it no longer applies.
        $this->add($product);
        $this->assertSame('50.00', $this->cart()['totals']['total']['amount']);

        app(OrderWorkflow::class)->transition(Order::query()->sole(), OrderStatus::Cancelled);
        $this->assertSame(0, $promotion->refresh()->times_used);
        $this->assertSame(0, PromotionRedemption::query()->count());
        $this->assertSame('45.00', $this->cart()['totals']['total']['amount']);
    }

    public function test_a_promotion_used_up_while_checking_out_aborts_the_order(): void
    {
        $promotion = $this->promotion([['type' => 'percent_off', 'data' => ['percent' => 10]]], attributes: ['usage_limit' => 1, 'name' => 'Flash']);
        $this->add($this->product('50.00'));

        // A parallel checkout takes the last use after this cart's totals were calculated.
        app(PipelineRegistry::class)->stage(CartCalculator::PIPELINE, function ($totals, $next) use ($promotion) {
            Promotion::query()->whereKey($promotion->id)->update(['times_used' => 1]);

            return $next($totals);
        }, 999);

        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id))->assertSessionHas('error');

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, PromotionRedemption::query()->count());
    }

    public function test_per_customer_limit_counts_guest_emails(): void
    {
        $this->promotion([['type' => 'percent_off', 'data' => ['percent' => 10]]], attributes: ['usage_limit_per_customer' => 1, 'name' => 'Once']);
        $product = $this->product('50.00');

        $this->add($product);
        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id))->assertSessionMissing('error');

        // Same email again: the offer no longer applies, the order is at full price.
        $this->add($product);
        $this->post(route('checkout.store'), $this->checkoutData($this->payment->id))->assertSessionMissing('error');
        $this->assertSame('50.00', (string) Order::query()->latest('id')->firstOrFail()->total->getAmount());
        $this->assertSame(1, PromotionRedemption::query()->count());
    }

    public function test_refunds_return_what_was_paid_for_discounted_lines(): void
    {
        $this->promotion([['type' => 'percent_off', 'data' => ['percent' => 25]]]);
        $this->add($this->product('40.00'), 2);
        $this->post(route('checkout.store'), $this->checkoutData(PaymentMethod::factory()->create(['gateway' => 'bank_transfer'])->id))->assertSessionMissing('error');

        $order = Order::query()->sole();
        app(OrderWorkflow::class)->transition($order, PaymentStatus::Paid);

        $refund = app(RefundService::class)->refund($order->refresh(), [$order->items->sole()->id => 1]);

        $this->assertSame('30.00', (string) $refund->amount->getAmount());
    }

    public function test_coupon_code_lookup_ignores_case_and_inactive_codes_are_refused(): void
    {
        $promotion = $this->promotion([['type' => 'percent_off', 'data' => ['percent' => 5]]], attributes: ['requires_coupon' => true]);
        Coupon::query()->create(['promotion_id' => $promotion->id, 'code' => 'off', 'is_active' => false]);
        $this->add($this->product('10.00'));

        $this->post(route('cart.coupon.store'), ['code' => 'OFF'])->assertSessionHasErrors('code');
        $this->assertSame('OFF', Coupon::query()->sole()->code);
    }
}
