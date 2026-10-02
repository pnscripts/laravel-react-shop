<?php

namespace PnShop\Returns\Policies;

use PnShop\Acl\Policies\PermissionPolicy;

class ReturnRequestPolicy extends PermissionPolicy
{
    protected string $permission = 'sales.returns.manage';
}
