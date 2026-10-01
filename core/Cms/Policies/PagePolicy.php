<?php

namespace PnShop\Cms\Policies;

use PnShop\Acl\Policies\PermissionPolicy;

class PagePolicy extends PermissionPolicy
{
    protected string $permission = 'content.pages.manage';
}
