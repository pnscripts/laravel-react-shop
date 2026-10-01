<?php

namespace PnShop\Plugins\HandlingFee\Policies;

use PnShop\Acl\Policies\PermissionPolicy;

class ExemptionPolicy extends PermissionPolicy
{
    protected string $permission = 'handling_fee.manage';
}
