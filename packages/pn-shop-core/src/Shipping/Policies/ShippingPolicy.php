<?php

namespace PnShop\Shipping\Policies;

use PnShop\Acl\Policies\PermissionPolicy;

class ShippingPolicy extends PermissionPolicy
{
    protected string $permission = 'store.shipping.manage';
}
