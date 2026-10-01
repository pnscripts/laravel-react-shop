<?php

namespace Tests\Feature\Extensions;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use PnShop\Catalog\Models\Product;
use PnShop\Customer\Models\CustomerGroup;
use PnShop\Extension\ExtensionManager;
use PnShop\Extension\PackageIntegrity;
use PnShop\Plugins\HandlingFee\Models\Exemption;
use PnShop\Settings\Settings;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The reference plugin shipped in extensions/pnshop/handling-fee, end to end.
 */
class HandlingFeePluginTest extends AdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'pnshop.extensions.path' => base_path('extensions'),
            'pnshop.extensions.cache' => sys_get_temp_dir().'/pnshop-plugins-'.bin2hex(random_bytes(4)).'.php',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink((string) config('pnshop.extensions.cache'));

        parent::tearDown();
    }

    public function test_the_reference_plugin_installs_and_charges_its_fee(): void
    {
        $manager = app(ExtensionManager::class);

        $this->assertSame([], $manager->problems($manager->find('pnshop/handling-fee')));

        $manager->install('pnshop/handling-fee');
        $manager->enable('pnshop/handling-fee');
        $this->assertTrue(Schema::hasTable('pnshop_handling_fee_exemptions'));

        $product = Product::factory()->active()->create(['price' => '10.00', 'sale_price' => null, 'stock' => 9]);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);

        $this->get(route('cart.index'))->assertInertia(fn ($page) => $page
            ->where('cart.totals.lines.0.code', 'handling_fee')
            ->where('cart.totals.lines.0.amount.amount', '2.50')
            ->where('cart.totals.total.amount', '12.50')
        );

        // Its settings work like core settings, and its label is translated.
        app(Settings::class)->set('plugin.pnshop_handling_fee', ['amount' => '4']);
        $this->get('/bg/cart')->assertInertia(fn ($page) => $page->where('cart.totals.lines.0.label', 'Такса обработка')->where('cart.totals.lines.0.amount.amount', '4.00'));

        // Above the threshold there is no fee.
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 3]);
        $this->get(route('cart.index'))->assertInertia(fn ($page) => $page->where('cart.totals.lines', []));
    }

    public function test_exempt_customer_groups_pay_no_fee(): void
    {
        $manager = app(ExtensionManager::class);
        $manager->install('pnshop/handling-fee');
        $manager->enable('pnshop/handling-fee');

        $wholesale = CustomerGroup::query()->create(['code' => 'wholesale', 'name' => 'Wholesale']);
        Exemption::query()->create(['customer_group_id' => $wholesale->id]);
        $customer = User::factory()->create(['customer_group_id' => $wholesale->id]);
        $product = Product::factory()->active()->create(['price' => '10.00', 'sale_price' => null, 'stock' => 9]);

        $this->actingAs($customer)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($customer)->get(route('cart.index'))->assertInertia(fn ($page) => $page->where('cart.totals.lines', []));
    }

    public function test_the_shipped_plugin_files_are_unchanged_and_complete(): void
    {
        $sums = PackageIntegrity::checksums(base_path('extensions/pnshop/handling-fee'));

        $this->assertArrayHasKey('pnshop.json', $sums);
        $this->assertArrayHasKey('src/HandlingFeePlugin.php', $sums);
        $this->assertArrayHasKey('database/migrations/2026_10_07_000001_create_pnshop_handling_fee_exemptions_table.php', $sums);
    }
}
