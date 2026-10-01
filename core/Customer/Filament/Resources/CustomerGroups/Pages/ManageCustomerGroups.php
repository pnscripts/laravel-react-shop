<?php

namespace PnShop\Customer\Filament\Resources\CustomerGroups\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use PnShop\Customer\Filament\Resources\CustomerGroups\CustomerGroupResource;

class ManageCustomerGroups extends ManageRecords
{
    protected static string $resource = CustomerGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
