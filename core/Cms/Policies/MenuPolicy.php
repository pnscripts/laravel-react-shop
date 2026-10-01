<?php

namespace PnShop\Cms\Policies;

use PnShop\Acl\Policies\PermissionPolicy;

class MenuPolicy extends PermissionPolicy
{
    protected string $permission = 'content.menus.manage';
}
