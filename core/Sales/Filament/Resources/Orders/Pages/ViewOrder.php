<?php

namespace PnShop\Sales\Filament\Resources\Orders\Pages;

use Filament\Resources\Pages\ViewRecord;
use PnShop\Sales\Filament\Actions\ChangeOrderStatusAction;
use PnShop\Sales\Filament\Resources\Orders\OrderResource;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [ChangeOrderStatusAction::make()];
    }
}
