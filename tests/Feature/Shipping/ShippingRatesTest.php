<?php

namespace Tests\Feature\Shipping;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PnShop\Cart\CartItemDTO;
use PnShop\Shipping\Carriers\RateTable;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\Models\ShippingZone;
use PnShop\Shipping\ShippingRequest;
use PnShop\Shipping\ShippingService;
use Tests\TestCase;

class ShippingRatesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, array<string, mixed>, int, int, string, string|null}>
     */
    public static function quotes(): array
    {
        return [
            'flat per order' => ['flat_rate', ['cost' => '4.50'], 3, 0, '10.00', '4.50'],
            'flat per item' => ['flat_rate', ['cost' => '2.00', 'per' => 'item'], 3, 0, '10.00', '6.00'],
            'free above threshold' => ['free_shipping', ['min_subtotal' => '50'], 5, 0, '10.00', '0.00'],
            'free below threshold' => ['free_shipping', ['min_subtotal' => '50'], 1, 0, '10.00', null],
            'pickup' => ['pickup', [], 1, 0, '10.00', '0.00'],
            'weight first band' => ['weight_based', ['rates' => "1000: 5\n5000: 9"], 2, 400, '10.00', '5.00'],
            'weight second band' => ['weight_based', ['rates' => "1000: 5\n5000: 9"], 2, 1500, '10.00', '9.00'],
            'weight too heavy' => ['weight_based', ['rates' => "1000: 5\n5000: 9"], 10, 1000, '10.00', null],
            'price low band' => ['price_based', ['rates' => "0: 7\n50: 4\n100: 0"], 1, 0, '30.00', '7.00'],
            'price free band' => ['price_based', ['rates' => "0: 7\n50: 4\n100: 0"], 4, 0, '30.00', '0.00'],
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    #[DataProvider('quotes')]
    public function test_carriers_price_requests(string $carrier, array $settings, int $quantity, int $weight, string $unitPrice, ?string $expected): void
    {
        $method = ShippingMethod::factory()->create(['carrier' => $carrier, 'settings' => $settings]);
        $unit = Money::of($unitPrice, 'USD');
        $request = new ShippingRequest(collect([new CartItemDTO(1, 1, 'Item', 'item', '', null, $unit, null, null, null, $quantity, $weight)]), $unit->multipliedBy($quantity), 'BG');

        $quote = $method->carrierInstance()?->quote($request, $method);

        $this->assertSame($expected, $quote === null ? null : (string) $quote->getAmount());
    }

    public function test_rate_tables_ignore_blank_lines_and_accept_commas(): void
    {
        $this->assertSame([[0.0, '7'], [49.5, '4.5']], RateTable::parse("49,5 : 4,5\n\n0: 7"));
    }

    public function test_the_first_matching_zone_by_position_is_used(): void
    {
        $sofia = ShippingZone::factory()->create(['name' => 'Sofia', 'countries' => ['BG'], 'postcodes' => ['1*'], 'position' => 1]);
        $bulgaria = ShippingZone::factory()->create(['name' => 'Bulgaria', 'countries' => ['BG'], 'position' => 2]);
        $world = ShippingZone::factory()->create(['name' => 'World', 'position' => 3]);

        $shipping = app(ShippingService::class);

        $this->assertTrue($shipping->zoneFor('BG', '1000')->is($sofia));
        $this->assertTrue($shipping->zoneFor('bg', '4000')->is($bulgaria));
        $this->assertTrue($shipping->zoneFor('DE', '10115')->is($world));
    }

    public function test_quotes_list_the_zones_active_methods_cheapest_first(): void
    {
        $zone = ShippingZone::factory()->create(['countries' => ['BG']]);
        ShippingMethod::factory()->for($zone, 'zone')->create(['name' => 'Express', 'settings' => ['cost' => '9.00']]);
        ShippingMethod::factory()->for($zone, 'zone')->create(['name' => 'Standard', 'settings' => ['cost' => '4.00']]);
        ShippingMethod::factory()->for($zone, 'zone')->create(['name' => 'Off', 'is_active' => false]);
        ShippingMethod::factory()->for($zone, 'zone')->create(['name' => 'Missing carrier', 'carrier' => 'acme']);
        ShippingMethod::factory()->create(['name' => 'Other zone']);

        $unit = Money::of('10', 'USD');
        $quotes = app(ShippingService::class)->quotes(new ShippingRequest(collect([new CartItemDTO(1, 1, 'Item', 'item', '', null, $unit, null, null, null, 1)]), $unit, 'BG'));

        $this->assertSame(['Standard', 'Express'], $quotes->map(fn ($quote) => $quote->method->name)->all());
    }
}
