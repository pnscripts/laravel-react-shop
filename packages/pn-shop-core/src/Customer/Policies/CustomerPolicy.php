<?php

namespace PnShop\Customer\Policies;

use PnShop\Acl\Models\AdminUser;
use PnShop\Customer\Models\User;

/**
 * Staff access to customer accounts. Customers themselves never pass through this policy.
 */
class CustomerPolicy
{
    public function viewAny(AdminUser $admin): bool
    {
        return $admin->can('customers.view');
    }

    public function view(AdminUser $admin, User $customer): bool
    {
        return $admin->can('customers.view');
    }

    public function update(AdminUser $admin, User $customer): bool
    {
        return $admin->can('customers.manage');
    }
}
