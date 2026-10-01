<?php

namespace PnShop\Sales\Policies;

use PnShop\Acl\Models\AdminUser;
use PnShop\Sales\Models\Order;

class OrderPolicy
{
    public function viewAny(AdminUser $admin): bool
    {
        return $admin->can('sales.orders.view');
    }

    public function view(AdminUser $admin, Order $order): bool
    {
        return $admin->can('sales.orders.view');
    }

    public function update(AdminUser $admin, Order $order): bool
    {
        return $admin->can('sales.orders.update');
    }
}
