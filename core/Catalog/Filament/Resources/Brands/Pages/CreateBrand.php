<?php

namespace PnShop\Catalog\Filament\Resources\Brands\Pages;

use Filament\Resources\Pages\CreateRecord;
use PnShop\Catalog\Filament\Resources\Brands\BrandResource;
use PnShop\Localization\Filament\SavesTranslations;

class CreateBrand extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = BrandResource::class;
}
