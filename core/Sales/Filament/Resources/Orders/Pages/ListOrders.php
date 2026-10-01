<?php

namespace PnShop\Sales\Filament\Resources\Orders\Pages;

use Filament\Resources\Pages\ListRecords;
use PnShop\Sales\Filament\Resources\Orders\OrderResource;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
