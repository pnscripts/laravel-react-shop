<?php

namespace PnShop\Catalog\Filament\Resources\Options\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use PnShop\Catalog\Filament\Resources\Options\OptionResource;
use PnShop\Localization\Filament\SavesTranslations;

class EditOption extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = OptionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
