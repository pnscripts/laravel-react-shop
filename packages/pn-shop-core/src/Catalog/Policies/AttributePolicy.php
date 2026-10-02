<?php

namespace PnShop\Catalog\Policies;

use PnShop\Acl\Policies\PermissionPolicy;

class AttributePolicy extends PermissionPolicy
{
    protected string $permission = 'catalog.attributes.manage';
}
