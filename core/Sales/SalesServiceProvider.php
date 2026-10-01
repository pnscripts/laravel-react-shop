<?php

namespace PnShop\Sales;

use App\Models\Order;
use Illuminate\Support\Facades\Gate;
use PnShop\Foundation\Extension\Permission;
use PnShop\Foundation\ModuleServiceProvider;
use PnShop\Sales\Policies\OrderPolicy;

/**
 * Sales module. Order models still live in app/Models until the order redesign.
 */
class SalesServiceProvider extends ModuleServiceProvider
{
    protected function permissions(): array
    {
        return [
            new Permission('sales.orders.view', 'View orders', 'Sales'),
            new Permission('sales.orders.update', 'Change order status', 'Sales'),
        ];
    }

    protected function bootModule(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);
    }
}
