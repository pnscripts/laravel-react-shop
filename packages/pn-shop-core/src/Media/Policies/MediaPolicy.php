<?php

namespace PnShop\Media\Policies;

use PnShop\Acl\Policies\PermissionPolicy;

class MediaPolicy extends PermissionPolicy
{
    protected string $permission = 'content.media.manage';
}
