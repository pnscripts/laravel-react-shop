<?php

namespace PnShop\Localization\Filament\Resources\Languages\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use PnShop\Localization\Filament\Resources\Languages\LanguageResource;

class ListLanguages extends ListRecords
{
    protected static string $resource = LanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
