<?php

namespace Tests\Feature\Payment;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Catalog\Models\Product;
use PnShop\Payment\Gateways\ManualGateway;
use PnShop\Payment\Models\Payment;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Payment\PaymentGatewayManager;
use PnShop\Payment\PaymentResult;
use PnShop\Payment\PaymentState;
use PnShop\Sales\Models\Order;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\OrderStatus;
use PnShop\Sales\States\PaymentStatus;
use RuntimeException;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = Product::factory()->active()->create(['price' => '20.00', 'sale_price' => null, 'stock' => 10]);
    }

    public function test_bank_transfer_orders_wait_for_payment_and_show_the_bank_details(): void
    {
        $method = PaymentMethod::factory()->create([
            'gateway' => 'bank_transfer',
            'settings' => ['iban' => 'BG80BNBG96611020345678', 'account_holder' => 'PN Demo Ltd'],
        ]);

        $order = $this->checkout($method);

        $payment = $order->payments()->sole();
        $this->assertSame(PaymentState::Pending, $payment->status);
        $this->assertSame('20.00', (string) $payment->amount->getAmount());
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);

        $this->get(route('orders.show', $order))->assertInertia(fn ($page) => $page
            ->where('order.payment_instructions', fn (string $text) => str_contains($text, 'BG80BNBG96611020345678')
                && str_contains($text, (string) $order->number)
                && str_contains($text, '$20.00'))
        );
    }

    public function test_marking_an_order_paid_settles_its_payment(): void
    {
        $order = $this->checkout(PaymentMethod::factory()->create(['gateway' => 'cash_on_delivery']));

        app(OrderWorkflow::class)->transition($order, PaymentStatus::Paid);

        $payment = $order->payments()->sole();
        $this->assertSame(PaymentState::Paid, $payment->status);
        $this->assertSame(['initiate', 'manual'], $payment->transactions()->pluck('type')->all());

        $this->get(route('orders.show', $order))->assertInertia(fn ($page) => $page->where('order.payment_instructions', null));
    }

    public function test_cancelling_an_order_cancels_its_open_payment(): void
    {
        $order = $this->checkout(PaymentMethod::factory()->create(['gateway' => 'cash_on_delivery']));

        app(OrderWorkflow::class)->transition($order, OrderStatus::Cancelled);

        $this->assertSame(PaymentState::Cancelled, $order->payments()->sole()->status);
    }

    public function test_methods_outside_their_rules_are_not_offered_or_accepted(): void
    {
        $everywhere = PaymentMethod::factory()->create(['gateway' => 'cash_on_delivery', 'name' => 'Everywhere']);
        $big = PaymentMethod::factory()->create(['gateway' => 'bank_transfer', 'name' => 'Big orders', 'min_total' => '100.00']);
        $germany = PaymentMethod::factory()->create(['gateway' => 'bank_transfer', 'name' => 'Germany only', 'countries' => ['DE']]);
        PaymentMethod::factory()->create(['gateway' => 'not_installed', 'name' => 'Missing gateway']);
        PaymentMethod::factory()->create(['gateway' => 'cash_on_delivery', 'name' => 'Off', 'is_active' => false]);

        $this->post(route('cart.store'), ['product_id' => $this->product->id, 'quantity' => 1]);

        $this->get(route('checkout.create'))->assertInertia(fn ($page) => $page
            ->where('paymentMethods', fn ($methods) => collect($methods)->pluck('name')->sort()->values()->all() === ['Everywhere', 'Germany only'])
        );

        $this->post(route('checkout.store'), $this->checkoutData($big->id))->assertSessionHas('error');
        $this->post(route('checkout.store'), $this->checkoutData($germany->id))->assertSessionHas('error');
        $this->assertSame(0, Order::query()->count());

        $this->post(route('checkout.store'), $this->checkoutData($everywhere->id))->assertSessionHasNoErrors();
        $this->assertSame(1, Order::query()->count());
    }

    public function test_an_immediately_paid_gateway_marks_the_order_paid(): void
    {
        $this->fakeGateway('instant', fn () => PaymentResult::paid('txn_123'));

        $order = $this->checkout(PaymentMethod::factory()->create(['gateway' => 'instant']));

        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Processing, $order->status);
        $this->assertSame('txn_123', $order->payments()->sole()->reference);
    }

    public function test_redirecting_gateways_send_the_customer_to_the_provider(): void
    {
        $this->fakeGateway('offsite', fn () => PaymentResult::redirect('https://pay.example.test/session/1'));
        $method = PaymentMethod::factory()->create(['gateway' => 'offsite']);

        $this->post(route('cart.store'), ['product_id' => $this->product->id, 'quantity' => 1]);

        $this->withHeader('X-Inertia', 'true')
            ->post(route('checkout.store'), $this->checkoutData($method->id))
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', 'https://pay.example.test/session/1');

        $this->assertSame(PaymentState::Pending, Payment::query()->sole()->status);
    }

    public function test_a_crashing_gateway_keeps_the_order_and_marks_the_payment_failed(): void
    {
        $this->fakeGateway('broken', fn () => throw new RuntimeException('provider down'));

        $order = $this->checkout(PaymentMethod::factory()->create(['gateway' => 'broken']), expectError: true);

        $this->assertSame(PaymentStatus::Failed, $order->payment_status);
        $this->assertSame(PaymentState::Failed, $order->payments()->sole()->status);
        $this->assertStringNotContainsString('provider down', (string) $order->payments()->sole()->transactions()->sole()->message);
    }

    private function checkout(PaymentMethod $method, bool $expectError = false): Order
    {
        $this->post(route('cart.store'), ['product_id' => $this->product->id, 'quantity' => 1]);

        $response = $this->post(route('checkout.store'), $this->checkoutData($method->id))->assertSessionHasNoErrors();

        $expectError ? $response->assertSessionHas('error') : $response->assertSessionMissing('error');

        return Order::query()->latest('id')->firstOrFail();
    }

    private function fakeGateway(string $code, \Closure $initiate): void
    {
        app(PaymentGatewayManager::class)->register(new class($code, $initiate) extends ManualGateway
        {
            public function __construct(private string $gatewayCode, private \Closure $onInitiate) {}

            public function code(): string
            {
                return $this->gatewayCode;
            }

            public function label(): string
            {
                return 'Fake';
            }

            public function settings(): array
            {
                return [];
            }

            public function initiate(Payment $payment, PaymentMethod $method): PaymentResult
            {
                return ($this->onInitiate)($payment);
            }

            public function refund(Payment $payment, Money $amount, PaymentMethod $method): PaymentResult
            {
                return PaymentResult::refunded();
            }
        });
    }
}
