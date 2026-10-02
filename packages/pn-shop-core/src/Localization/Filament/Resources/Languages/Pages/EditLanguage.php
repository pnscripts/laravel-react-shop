<?php

namespace PnShop\Localization\Filament\Resources\Languages\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use PnShop\Localization\Filament\Resources\Languages\LanguageResource;

class EditLanguage extends EditRecord
{
    protected static string $resource = LanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
