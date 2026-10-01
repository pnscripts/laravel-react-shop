<?php

namespace PnShop\Sales\Filament\Resources\Orders\Pages;

use Filament\Resources\Pages\ViewRecord;
use PnShop\Sales\Filament\Actions\OrderStateActions;
use PnShop\Sales\Filament\Resources\Orders\OrderResource;
use PnShop\Shipping\Filament\Actions\CreateShipmentAction;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return 'Order '.$this->getRecord()->getAttribute('number');
    }

    protected function getHeaderActions(): array
    {
        return [CreateShipmentAction::make(), ...OrderStateActions::all()];
    }
}
