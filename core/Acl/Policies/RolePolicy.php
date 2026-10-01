<?php

namespace PnShop\Acl\Policies;

use PnShop\Acl\Models\AdminUser;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(AdminUser $admin): bool
    {
        return $admin->can('system.roles.manage');
    }

    public function view(AdminUser $admin, Role $role): bool
    {
        return $admin->can('system.roles.manage');
    }

    public function create(AdminUser $admin): bool
    {
        return $admin->can('system.roles.manage');
    }

    /**
     * The administrator role always has every permission, so it is not editable.
     */
    public function update(AdminUser $admin, Role $role): bool
    {
        return $admin->can('system.roles.manage') && $role->name !== AdminUser::ADMINISTRATOR_ROLE;
    }

    public function delete(AdminUser $admin, Role $role): bool
    {
        return $this->update($admin, $role) && $role->users()->count() === 0;
    }
}
