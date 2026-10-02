<?php

namespace PnShop\Shipping\Filament\Resources\ShippingMethods\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use PnShop\Shipping\Filament\Resources\ShippingMethods\ShippingMethodResource;

class ListShippingMethods extends ListRecords
{
    protected static string $resource = ShippingMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
