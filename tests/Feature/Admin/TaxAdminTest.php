<?php

namespace Tests\Feature\Admin;

use Livewire\Livewire;
use PnShop\Tax\Filament\Resources\TaxClasses\Pages\ManageTaxClasses;
use PnShop\Tax\Filament\Resources\TaxZones\Pages\CreateTaxZone;
use PnShop\Tax\Filament\Resources\TaxZones\Pages\EditTaxZone;
use PnShop\Tax\Filament\Resources\TaxZones\RelationManagers\RatesRelationManager;
use PnShop\Tax\Models\TaxClass;
use PnShop\Tax\Models\TaxZone;

class TaxAdminTest extends AdminTestCase
{
    public function test_classes_zones_and_rates_are_managed_in_the_admin(): void
    {
        $this->actingAsAdministrator();

        Livewire::test(ManageTaxClasses::class)
            ->callAction('create', ['name' => 'Standard', 'is_default' => true])
            ->assertHasNoActionErrors();

        Livewire::test(CreateTaxZone::class)
            ->fillForm(['name' => 'Bulgaria', 'countries' => ['BG']])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(EditTaxZone::getUrl(['record' => TaxZone::query()->sole()]));

        Livewire::test(RatesRelationManager::class, ['ownerRecord' => TaxZone::query()->sole(), 'pageClass' => EditTaxZone::class])
            ->callTableAction('create', data: ['name' => 'VAT 20%', 'tax_class_id' => TaxClass::query()->sole()->id, 'rate' => 20])
            ->assertHasNoTableActionErrors()
            ->assertSee('VAT 20%');

        $this->assertSame('20.0000', TaxZone::query()->sole()->rates()->sole()->rate);
    }

    public function test_staff_without_permission_cannot_manage_tax(): void
    {
        $this->actingAsStaff(['catalog.products.view']);

        $this->get(ManageTaxClasses::getUrl())->assertForbidden();
    }
}
