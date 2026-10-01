<?php

namespace PnShop\Acl\Policies;

use PnShop\Acl\Models\AdminUser;

class AdminUserPolicy
{
    public function viewAny(AdminUser $admin): bool
    {
        return $admin->can('system.admin_users.manage');
    }

    public function view(AdminUser $admin, AdminUser $model): bool
    {
        return $admin->can('system.admin_users.manage');
    }

    public function create(AdminUser $admin): bool
    {
        return $admin->can('system.admin_users.manage');
    }

    /**
     * Only administrators change administrators (their email, password or roles), so staff
     * who manage accounts cannot take over a more powerful account.
     */
    public function update(AdminUser $admin, AdminUser $model): bool
    {
        return $admin->can('system.admin_users.manage')
            && ($admin->isAdministrator() || ! $model->isAdministrator());
    }

    /**
     * Nobody can delete their own account, and the last administrator cannot be deleted.
     */
    public function delete(AdminUser $admin, AdminUser $model): bool
    {
        return $admin->can('system.admin_users.manage')
            && $admin->isNot($model)
            && ($admin->isAdministrator() || ! $model->isAdministrator())
            && ! ($model->isAdministrator() && self::administratorCount() <= 1);
    }

    public static function administratorCount(): int
    {
        return AdminUser::role(AdminUser::ADMINISTRATOR_ROLE, 'admin')->where('is_active', true)->count();
    }
}
