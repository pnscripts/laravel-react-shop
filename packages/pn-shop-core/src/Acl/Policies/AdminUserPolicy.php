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
     * Staff who manage accounts may only change (email, password, roles) accounts that hold
     * nothing they do not hold themselves, so they cannot take over a more powerful account.
     * Only administrators change administrators.
     */
    public function update(AdminUser $admin, AdminUser $model): bool
    {
        return $admin->can('system.admin_users.manage') && self::outranks($admin, $model);
    }

    /**
     * Nobody can delete their own account, and the last administrator cannot be deleted.
     */
    public function delete(AdminUser $admin, AdminUser $model): bool
    {
        return $admin->can('system.admin_users.manage')
            && $admin->isNot($model)
            && self::outranks($admin, $model)
            && ! ($model->isAdministrator() && self::administratorCount() <= 1);
    }

    /**
     * Whether $admin holds everything $model can do: always for administrators; never over an
     * administrator otherwise; for other staff, every permission of the target's roles.
     */
    private static function outranks(AdminUser $admin, AdminUser $model): bool
    {
        if ($admin->isAdministrator()) {
            return true;
        }

        return ! $model->isAdministrator()
            && $admin->mayGrant($model->getAllPermissions()->pluck('name')->all());
    }

    public static function administratorCount(): int
    {
        return AdminUser::role(AdminUser::ADMINISTRATOR_ROLE, 'admin')->where('is_active', true)->count();
    }
}
