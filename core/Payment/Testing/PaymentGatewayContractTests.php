<?php

namespace PnShop\Payment\Testing;

use Brick\Money\Money;
use PnShop\Payment\Contracts\PaymentGateway;
use PnShop\Payment\Models\Payment;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Payment\PaymentContext;
use PnShop\Payment\PaymentOutcome;
use PnShop\Payment\PaymentResult;
use PnShop\Sales\Models\Order;
use PnShop\Settings\SettingDefinition;
use Tests\TestCase;

/**
 * The contract every payment gateway must meet. Use it in a test case for your gateway:
 *
 *     class MyGatewayTest extends TestCase
 *     {
 *         use RefreshDatabase, PaymentGatewayContractTests;
 *
 *         protected function gateway(): PaymentGateway { return new MyGateway(); }
 *     }
 *
 * @mixin TestCase
 */
trait PaymentGatewayContractTests
{
    abstract protected function gateway(): PaymentGateway;

    /**
     * Settings the gateway needs to work in tests (sandbox keys, ...).
     *
     * @return array<string, mixed>
     */
    protected function gatewaySettings(): array
    {
        return [];
    }

    public function test_gateway_code_is_a_stable_identifier(): void
    {
        $this->assertMatchesRegularExpression('/^[a-z][a-z0-9_]{1,63}$/', $this->gateway()->code());
        $this->assertNotSame('', trim($this->gateway()->label()));
    }

    public function test_gateway_settings_are_unique_typed_definitions(): void
    {
        $settings = $this->gateway()->settings();

        $keys = array_map(fn (SettingDefinition $definition) => $definition->key, $settings);
        $this->assertSame(array_values(array_unique($keys)), $keys, 'Setting keys must be unique.');

        foreach ($settings as $definition) {
            $this->assertMatchesRegularExpression('/^[a-z][a-z0-9_]*$/', $definition->key);

            if ($definition->default !== null) {
                $this->assertTrue(validator(['value' => $definition->default], ['value' => $definition->validationRules()])->passes(), "The default of [{$definition->key}] fails its own rules.");
            }
        }
    }

    public function test_gateway_checks_availability_without_side_effects(): void
    {
        [$method] = $this->contractFixture();
        $before = Payment::query()->count();

        $this->gateway()->isAvailable(new PaymentContext(Money::of('10.00', 'USD'), 'BG'), $method);

        $this->assertSame($before, Payment::query()->count(), 'Checking availability must not create payments.');
    }

    public function test_initiating_a_payment_returns_a_usable_result(): void
    {
        [$method, $payment] = $this->contractFixture();

        $result = $this->gateway()->initiate($payment, $method);

        $this->assertInstanceOf(PaymentResult::class, $result);
        $this->assertNotSame(PaymentOutcome::Refunded, $result->outcome, 'Initiating cannot refund.');

        if ($result->outcome === PaymentOutcome::Redirect) {
            $this->assertNotNull($result->redirectUrl);
            $this->assertStringStartsWith('https://', (string) $result->redirectUrl);
        }

        if ($result->outcome === PaymentOutcome::Failed) {
            $this->assertNotEmpty($result->message, 'A failure needs a message that can be shown.');
        }
    }

    public function test_refunds_match_what_the_gateway_declares(): void
    {
        [$method, $payment] = $this->contractFixture();

        if (! $this->gateway()->supportsRefunds()) {
            $this->markTestSkipped('The gateway does not support refunds.');
        }

        $result = $this->gateway()->refund($payment, Money::of('5.00', 'USD'), $method);

        $this->assertContains($result->outcome, [PaymentOutcome::Refunded, PaymentOutcome::Failed]);
    }

    public function test_instructions_are_text_or_nothing(): void
    {
        [$method, $payment] = $this->contractFixture();

        $instructions = $this->gateway()->instructions($payment, $method);

        $this->assertTrue($instructions === null || trim($instructions) !== '');
    }

    /**
     * @return array{PaymentMethod, Payment}
     */
    private function contractFixture(): array
    {
        $order = Order::factory()->create(['currency' => 'USD', 'total' => '20.00', 'subtotal' => '20.00']);

        $method = PaymentMethod::query()->create([
            'name' => 'Contract test',
            'gateway' => $this->gateway()->code(),
            'settings' => $this->gatewaySettings(),
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'payment_method_id' => $method->id,
            'gateway' => $method->gateway,
            'currency' => 'USD',
            'amount' => Money::of('20.00', 'USD'),
        ]);

        return [$method, $payment];
    }
}
