<?php

namespace PnShop\Customer\Filament\Resources\Customers\Pages;

use Filament\Resources\Pages\EditRecord;
use PnShop\Customer\Filament\Resources\Customers\CustomerResource;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;
}
