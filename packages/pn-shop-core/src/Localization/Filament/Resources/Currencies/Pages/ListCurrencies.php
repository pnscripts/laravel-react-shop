<?php

namespace PnShop\Localization\Filament\Resources\Currencies\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use PnShop\Localization\Filament\Resources\Currencies\CurrencyResource;

class ListCurrencies extends ListRecords
{
    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
