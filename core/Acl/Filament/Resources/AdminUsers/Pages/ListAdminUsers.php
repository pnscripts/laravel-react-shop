<?php

namespace PnShop\Acl\Filament\Resources\AdminUsers\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use PnShop\Acl\Filament\Resources\AdminUsers\AdminUserResource;

class ListAdminUsers extends ListRecords
{
    protected static string $resource = AdminUserResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
