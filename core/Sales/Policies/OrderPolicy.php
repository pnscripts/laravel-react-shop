<?php

namespace PnShop\Sales\Policies;

use App\Models\Order;
use PnShop\Acl\Models\AdminUser;

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
