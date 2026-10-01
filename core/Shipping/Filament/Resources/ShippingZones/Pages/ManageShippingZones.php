<?php

namespace PnShop\Shipping\Filament\Resources\ShippingZones\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use PnShop\Shipping\Filament\Resources\ShippingZones\ShippingZoneResource;

class ManageShippingZones extends ManageRecords
{
    protected static string $resource = ShippingZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
