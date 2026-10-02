<?php

namespace PnShop\Catalog\Filament\Resources\Categories\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use PnShop\Catalog\Filament\Resources\Categories\CategoryResource;
use PnShop\Localization\Filament\SavesTranslations;

class EditCategory extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
