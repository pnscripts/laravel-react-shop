<?php

namespace PnShop\Tax\Policies;

use PnShop\Acl\Policies\PermissionPolicy;

class TaxPolicy extends PermissionPolicy
{
    protected string $permission = 'store.tax.manage';
}
