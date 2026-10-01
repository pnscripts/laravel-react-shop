<?php

namespace PnShop\Shipping\Testing;

use Brick\Money\Money;
use PnShop\Cart\CartItemDTO;
use PnShop\Settings\SettingDefinition;
use PnShop\Shipping\Contracts\ShippingCarrier;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\Models\ShippingZone;
use PnShop\Shipping\ShippingRequest;
use Tests\TestCase;

/**
 * The contract every shipping carrier must meet. Use it in a test case for your carrier:
 *
 *     class MyCarrierTest extends TestCase
 *     {
 *         use RefreshDatabase, ShippingCarrierContractTests;
 *
 *         protected function carrier(): ShippingCarrier { return new MyCarrier(); }
 *     }
 *
 * @mixin TestCase
 */
trait ShippingCarrierContractTests
{
    abstract protected function carrier(): ShippingCarrier;

    /**
     * Settings the carrier needs to quote in tests.
     *
     * @return array<string, mixed>
     */
    protected function carrierSettings(): array
    {
        return [];
    }

    public function test_carrier_code_is_a_stable_identifier(): void
    {
        $this->assertMatchesRegularExpression('/^[a-z][a-z0-9_]{1,63}$/', $this->carrier()->code());
        $this->assertNotSame('', trim($this->carrier()->label()));
    }

    public function test_carrier_settings_are_unique_and_their_defaults_valid(): void
    {
        $keys = array_map(fn (SettingDefinition $definition) => $definition->key, $this->carrier()->settings());

        $this->assertSame(array_values(array_unique($keys)), $keys, 'Setting keys must be unique.');

        foreach ($this->carrier()->settings() as $definition) {
            if ($definition->default !== null) {
                $this->assertTrue(validator(['value' => $definition->default], ['value' => $definition->validationRules()])->passes(), "The default of [{$definition->key}] fails its own rules.");
            }
        }
    }

    public function test_quotes_are_in_the_request_currency_and_never_negative(): void
    {
        $method = $this->carrierMethod();

        foreach ([[1, 100, '5.00'], [3, 2500, '80.00'], [10, 0, '250.00']] as [$quantity, $weight, $price]) {
            $quote = $this->carrier()->quote($this->carrierRequest($quantity, $weight, $price), $method);

            if ($quote === null) {
                continue;
            }

            $this->assertSame('USD', $quote->getCurrency()->getCurrencyCode());
            $this->assertFalse($quote->isNegative(), 'A shipping price cannot be negative.');
        }

        $this->addToAssertionCount(1);
    }

    public function test_tracking_links_are_https_or_absent(): void
    {
        $url = $this->carrier()->trackingUrl('AB 123/45', $this->carrierMethod());

        $this->assertTrue($url === null || str_starts_with($url, 'https://') || str_starts_with($url, 'http://'));
    }

    private function carrierMethod(): ShippingMethod
    {
        return ShippingMethod::query()->create([
            'shipping_zone_id' => ShippingZone::query()->create(['name' => 'Contract'])->id,
            'name' => 'Contract test',
            'carrier' => $this->carrier()->code(),
            'settings' => $this->carrierSettings(),
        ]);
    }

    private function carrierRequest(int $quantity, int $weight, string $price): ShippingRequest
    {
        $unit = Money::of($price, 'USD');
        $item = new CartItemDTO(1, 1, 'Item', 'item', '', null, $unit, null, null, null, $quantity, $weight);

        return new ShippingRequest(collect([$item]), $unit->multipliedBy($quantity), 'BG', '1000');
    }
}
