<?php

namespace PnShop\Localization\Policies;

use Illuminate\Database\Eloquent\Model;
use PnShop\Acl\Models\AdminUser;

/**
 * Languages, currencies and countries share one permission.
 * The default language and currency cannot be deleted.
 */
class LocalizationPolicy
{
    public function viewAny(AdminUser $admin): bool
    {
        return $admin->can('store.localization.manage');
    }

    public function view(AdminUser $admin, Model $model): bool
    {
        return $admin->can('store.localization.manage');
    }

    public function create(AdminUser $admin): bool
    {
        return $admin->can('store.localization.manage');
    }

    public function update(AdminUser $admin, Model $model): bool
    {
        return $admin->can('store.localization.manage');
    }

    public function delete(AdminUser $admin, Model $model): bool
    {
        return $admin->can('store.localization.manage') && ! $model->getAttribute('is_default');
    }
}
