<?php

namespace PnShop\Customer\Filament\Resources\Customers\Pages;

use Filament\Resources\Pages\ListRecords;
use PnShop\Customer\Filament\Resources\Customers\CustomerResource;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;
}
