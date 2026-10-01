<?php

namespace PnShop\Catalog\Filament\Resources\Products\Pages;

use Filament\Resources\Pages\CreateRecord;
use PnShop\Catalog\Filament\Resources\Products\ProductResource;
use PnShop\Localization\Filament\SavesTranslations;

class CreateProduct extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = ProductResource::class;
}
