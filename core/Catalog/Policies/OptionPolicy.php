<?php

namespace PnShop\Catalog\Policies;

use PnShop\Acl\Policies\PermissionPolicy;

class OptionPolicy extends PermissionPolicy
{
    protected string $permission = 'catalog.options.manage';
}
