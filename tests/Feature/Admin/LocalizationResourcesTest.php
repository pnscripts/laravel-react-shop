<?php

namespace Tests\Feature\Admin;

use Livewire\Livewire;
use PnShop\Localization\Filament\Resources\Currencies\Pages\CreateCurrency;
use PnShop\Localization\Filament\Resources\Languages\Pages\CreateLanguage;
use PnShop\Localization\Filament\Resources\Languages\Pages\EditLanguage;
use PnShop\Localization\Filament\Resources\Languages\Pages\ListLanguages;
use PnShop\Localization\Localization;
use PnShop\Localization\Models\Language;
use Tighten\Ziggy\Ziggy;

class LocalizationResourcesTest extends AdminTestCase
{
    public function test_a_language_can_be_added_and_is_served_immediately(): void
    {
        $this->actingAsAdministrator();

        Livewire::test(CreateLanguage::class)
            ->fillForm(['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(app(Localization::class)->isSupported('de'));
        $this->get('/de/shop')->assertOk();
    }

    public function test_the_default_language_cannot_be_deleted(): void
    {
        $this->actingAsAdministrator();
        $english = Language::query()->where('code', 'en')->sole();

        Livewire::test(ListLanguages::class)->assertTableActionHidden('delete', $english);
        Livewire::test(EditLanguage::class, ['record' => $english->getRouteKey()])->assertActionHidden('delete');
    }

    public function test_currency_codes_must_be_iso_4217(): void
    {
        $this->actingAsAdministrator();

        Livewire::test(CreateCurrency::class)
            ->fillForm(['code' => 'ZZZ', 'name' => 'Fake', 'exchange_rate' => 1])
            ->call('create')
            ->assertHasFormErrors(['code']);
    }

    public function test_localization_screens_need_their_permission(): void
    {
        $this->actingAsStaff(role: 'catalog-manager');

        $this->get('/admin/languages')->assertForbidden();
        $this->get('/admin/currencies')->assertForbidden();
        $this->get('/admin/countries')->assertForbidden();
    }

    public function test_admin_routes_are_not_published_to_the_storefront(): void
    {
        $routes = array_keys((new Ziggy)->toArray()['routes']);

        $this->assertContains('shop.index', $routes);
        $this->assertEmpty(array_filter($routes, fn (string $name) => str_starts_with($name, 'filament.') || str_starts_with($name, 'livewire.')));
    }
}
