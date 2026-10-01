<?php

namespace PnShop\Catalog\Filament\Resources\Brands\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use PnShop\Catalog\Filament\Resources\Brands\BrandResource;
use PnShop\Localization\Filament\SavesTranslations;

class EditBrand extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
