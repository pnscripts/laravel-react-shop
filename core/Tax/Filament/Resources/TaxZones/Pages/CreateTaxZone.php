<?php

namespace PnShop\Tax\Filament\Resources\TaxZones\Pages;

use Filament\Resources\Pages\CreateRecord;
use PnShop\Tax\Filament\Resources\TaxZones\TaxZoneResource;

class CreateTaxZone extends CreateRecord
{
    protected static string $resource = TaxZoneResource::class;

    protected function getRedirectUrl(): string
    {
        // Rates are added on the edit page.
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
