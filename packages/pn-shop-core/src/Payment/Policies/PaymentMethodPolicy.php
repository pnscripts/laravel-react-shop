<?php

namespace PnShop\Payment\Policies;

use PnShop\Acl\Policies\PermissionPolicy;

class PaymentMethodPolicy extends PermissionPolicy
{
    protected string $permission = 'store.payment_methods.manage';
}
