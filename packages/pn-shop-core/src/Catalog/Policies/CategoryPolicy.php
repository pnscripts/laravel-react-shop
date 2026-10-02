<?php

namespace PnShop\Catalog\Policies;

use PnShop\Acl\Policies\PermissionPolicy;

class CategoryPolicy extends PermissionPolicy
{
    protected string $permission = 'catalog.categories.manage';
}
