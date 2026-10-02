<?php

namespace PnShop\Promotion\Policies;

use PnShop\Acl\Policies\PermissionPolicy;

class PromotionPolicy extends PermissionPolicy
{
    protected string $permission = 'marketing.promotions.manage';
}
