<?php

namespace PnShop\Catalog\Filament\Resources\Products\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use PnShop\Catalog\Filament\Resources\Products\ProductResource;
use PnShop\Localization\Filament\SavesTranslations;

class EditProduct extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make(), RestoreAction::make()];
    }
}
