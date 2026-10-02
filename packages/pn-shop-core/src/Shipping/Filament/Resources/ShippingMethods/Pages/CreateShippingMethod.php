<?php

namespace PnShop\Shipping\Filament\Resources\ShippingMethods\Pages;

use Filament\Resources\Pages\CreateRecord;
use PnShop\Localization\Filament\SavesTranslations;
use PnShop\Shipping\Filament\Resources\ShippingMethods\ShippingMethodResource;

class CreateShippingMethod extends CreateRecord
{
    use SavesTranslations;

    protected static string $resource = ShippingMethodResource::class;
}
