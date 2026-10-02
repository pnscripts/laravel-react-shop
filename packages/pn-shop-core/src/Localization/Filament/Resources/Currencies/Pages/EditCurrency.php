<?php

namespace PnShop\Localization\Filament\Resources\Currencies\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use PnShop\Localization\Filament\Resources\Currencies\CurrencyResource;

class EditCurrency extends EditRecord
{
    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
