<?php

namespace PnShop\Acl\Filament\Resources\AdminUsers\Pages;

use Filament\Resources\Pages\CreateRecord;
use PnShop\Acl\Filament\Resources\AdminUsers\AdminUserResource;

class CreateAdminUser extends CreateRecord
{
    protected static string $resource = AdminUserResource::class;
}
