<?php

namespace PnShop\Shipping\Filament\Resources\ShippingMethods\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use PnShop\Localization\Filament\SavesTranslations;
use PnShop\Shipping\Filament\Resources\ShippingMethods\ShippingMethodResource;

class EditShippingMethod extends EditRecord
{
    use SavesTranslations;

    protected static string $resource = ShippingMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
