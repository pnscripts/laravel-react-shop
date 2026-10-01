<?php

namespace PnShop\Seo\Policies;

use PnShop\Acl\Policies\PermissionPolicy;

class RedirectPolicy extends PermissionPolicy
{
    protected string $permission = 'content.redirects.manage';
}
