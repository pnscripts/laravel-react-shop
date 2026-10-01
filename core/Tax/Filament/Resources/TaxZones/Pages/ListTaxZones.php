<?php

namespace PnShop\Tax\Filament\Resources\TaxZones\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use PnShop\Tax\Filament\Resources\TaxZones\TaxZoneResource;

class ListTaxZones extends ListRecords
{
    protected static string $resource = TaxZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
