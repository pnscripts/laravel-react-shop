<?php

namespace PnShop\Catalog\Filament\Resources\Attributes\Pages;

use Filament\Resources\Pages\CreateRecord;
use PnShop\Catalog\Filament\Resources\Attributes\AttributeResource;
use PnShop\Localization\Filament\SavesTranslations;

class CreateAttribute extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = AttributeResource::class;
}
