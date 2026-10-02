<?php

namespace PnShop\Catalog\Policies;

use PnShop\Acl\Models\AdminUser;
use PnShop\Catalog\Models\Product;

class ProductPolicy
{
    public function viewAny(AdminUser $admin): bool
    {
        return $admin->can('catalog.products.view');
    }

    public function view(AdminUser $admin, Product $product): bool
    {
        return $admin->can('catalog.products.view');
    }

    public function create(AdminUser $admin): bool
    {
        return $admin->can('catalog.products.create');
    }

    public function update(AdminUser $admin, Product $product): bool
    {
        return $admin->can('catalog.products.update');
    }

    public function delete(AdminUser $admin, Product $product): bool
    {
        return $admin->can('catalog.products.delete');
    }

    public function deleteAny(AdminUser $admin): bool
    {
        return $admin->can('catalog.products.delete');
    }

    public function restore(AdminUser $admin, Product $product): bool
    {
        return $admin->can('catalog.products.delete');
    }

    public function restoreAny(AdminUser $admin): bool
    {
        return $admin->can('catalog.products.delete');
    }

    public function forceDelete(AdminUser $admin, Product $product): bool
    {
        return $admin->can('catalog.products.delete');
    }

    public function forceDeleteAny(AdminUser $admin): bool
    {
        return $admin->can('catalog.products.delete');
    }
}
