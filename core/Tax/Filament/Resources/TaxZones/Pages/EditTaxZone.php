<?php

namespace PnShop\Tax\Filament\Resources\TaxZones\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use PnShop\Tax\Filament\Resources\TaxZones\TaxZoneResource;

class EditTaxZone extends EditRecord
{
    protected static string $resource = TaxZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
