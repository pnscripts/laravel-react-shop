<?php

namespace PnShop\Plugins\HandlingFee\Filament\Resources\Exemptions\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use PnShop\Plugins\HandlingFee\Filament\Resources\Exemptions\ExemptionResource;

class ManageExemptions extends ManageRecords
{
    protected static string $resource = ExemptionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
