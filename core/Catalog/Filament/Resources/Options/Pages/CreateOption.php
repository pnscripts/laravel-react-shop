<?php

namespace PnShop\Catalog\Filament\Resources\Options\Pages;

use Filament\Resources\Pages\CreateRecord;
use PnShop\Catalog\Filament\Resources\Options\OptionResource;
use PnShop\Localization\Filament\SavesTranslations;

class CreateOption extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = OptionResource::class;
}
