<?php

namespace PnShop\Tax\Filament\Resources\TaxClasses\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use PnShop\Tax\Filament\Resources\TaxClasses\TaxClassResource;

class ManageTaxClasses extends ManageRecords
{
    protected static string $resource = TaxClassResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
