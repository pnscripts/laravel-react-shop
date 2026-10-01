<?php

namespace Tests\Feature\Tax;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Tax\Models\TaxClass;
use PnShop\Tax\Models\TaxZone;
use PnShop\Tax\TableTaxProvider;
use PnShop\Tax\TaxableLine;
use PnShop\Tax\TaxRequest;
use PnShop\Tax\TaxResult;
use Tests\TestCase;

class TableTaxProviderTest extends TestCase
{
    use RefreshDatabase;

    private TaxClass $standard;

    private TaxClass $reduced;

    protected function setUp(): void
    {
        parent::setUp();

        $this->standard = TaxClass::query()->create(['name' => 'Standard', 'is_default' => true]);
        $this->reduced = TaxClass::query()->create(['name' => 'Reduced']);

        $bulgaria = TaxZone::query()->create(['name' => 'Bulgaria', 'countries' => ['BG'], 'position' => 1]);
        $bulgaria->rates()->create(['tax_class_id' => $this->standard->id, 'name' => 'VAT 20%', 'rate' => 20]);
        $bulgaria->rates()->create(['tax_class_id' => $this->reduced->id, 'name' => 'VAT 9%', 'rate' => 9]);
    }

    public function test_tax_is_extracted_from_gross_prices(): void
    {
        $result = $this->calculate([new TaxableLine('item:1', Money::of('120.00', 'USD'), null)], inclusive: true);

        $this->assertSame('20.00', (string) $result->total()->getAmount());
        $this->assertSame(['VAT 20%'], array_keys($result->byRate()));
    }

    public function test_tax_is_added_to_net_prices_per_class(): void
    {
        $result = $this->calculate([
            new TaxableLine('item:1', Money::of('100.00', 'USD'), $this->standard->id),
            new TaxableLine('item:2', Money::of('10.00', 'USD'), $this->reduced->id),
        ], inclusive: false);

        $this->assertSame('20.00', (string) $result->forLine('item:1')->getAmount());
        $this->assertSame('0.90', (string) $result->forLine('item:2')->getAmount());
        $this->assertSame('20.90', (string) $result->total()->getAmount());
    }

    public function test_compound_rates_apply_on_top_of_earlier_taxes(): void
    {
        $zone = TaxZone::query()->create(['name' => 'Quebec', 'countries' => ['CA'], 'postcodes' => ['G*', 'H*']]);
        $zone->rates()->create(['tax_class_id' => $this->standard->id, 'name' => 'GST', 'rate' => 5, 'priority' => 1]);
        $zone->rates()->create(['tax_class_id' => $this->standard->id, 'name' => 'QST', 'rate' => 10, 'priority' => 2, 'is_compound' => true]);

        $result = $this->calculate([new TaxableLine('item:1', Money::of('100.00', 'USD'), null)], inclusive: false, country: 'CA', postcode: 'H2X 1Y4');

        $this->assertSame(['GST' => '5.00', 'QST' => '10.50'], array_map(fn (Money $tax) => (string) $tax->getAmount(), $result->byRate()));

        // Gross prices give the same split back.
        $gross = $this->calculate([new TaxableLine('item:1', Money::of('115.50', 'USD'), null)], inclusive: true, country: 'CA', postcode: 'H2X 1Y4');
        $this->assertSame('15.50', (string) $gross->total()->getAmount());

        // Outside the postcodes there is no zone, so no tax.
        $this->assertTrue($this->calculate([new TaxableLine('item:1', Money::of('100.00', 'USD'), null)], inclusive: false, country: 'CA', postcode: 'T5J')->total()->isZero());
    }

    public function test_only_one_class_is_the_default(): void
    {
        $this->reduced->update(['is_default' => true]);

        $this->assertSame($this->reduced->id, TaxClass::defaultId());
        $this->assertFalse($this->standard->fresh()->is_default);
    }

    /**
     * @param  list<TaxableLine>  $lines
     */
    private function calculate(array $lines, bool $inclusive, string $country = 'BG', ?string $postcode = null): TaxResult
    {
        return (new TableTaxProvider)->calculate(new TaxRequest($lines, 'USD', $country, $postcode, $inclusive));
    }
}
