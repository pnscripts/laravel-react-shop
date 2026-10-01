<?php

namespace PnShop\Sales;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use PnShop\Foundation\Extension\Permission;
use PnShop\Foundation\ModuleServiceProvider;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Models\PaymentMethod;
use PnShop\Sales\Policies\OrderPolicy;

/**
 * Sales module: orders, checkout, payments.
 */
class SalesServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        Relation::morphMap([
            'order' => Order::class,
            'payment_method' => PaymentMethod::class,
        ]);
    }

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
