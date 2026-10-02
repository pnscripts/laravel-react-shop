<?php

namespace PnShop\Catalog\Policies;

use PnShop\Acl\Policies\PermissionPolicy;

class BrandPolicy extends PermissionPolicy
{
    protected string $permission = 'catalog.brands.manage';
}
