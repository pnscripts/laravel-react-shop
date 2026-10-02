<?php

namespace PnShop\Catalog\Filament\Resources\Categories\Pages;

use Filament\Resources\Pages\CreateRecord;
use PnShop\Catalog\Filament\Resources\Categories\CategoryResource;
use PnShop\Localization\Filament\SavesTranslations;

class CreateCategory extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = CategoryResource::class;
}
