<?php

namespace PnShop\Customer\Policies;

use Illuminate\Database\Eloquent\Model;
use PnShop\Acl\Models\AdminUser;
use PnShop\Acl\Policies\PermissionPolicy;

class CustomerGroupPolicy extends PermissionPolicy
{
    protected string $permission = 'customers.manage';

    /**
     * The default group cannot be deleted.
     */
    public function delete(AdminUser $admin, Model $model): bool
    {
        return parent::delete($admin, $model) && ! $model->getAttribute('is_default');
    }
}
