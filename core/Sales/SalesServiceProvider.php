<?php

namespace PnShop\Sales;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use PnShop\Foundation\Extension\Permission;
use PnShop\Foundation\ModuleServiceProvider;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Policies\OrderPolicy;
use PnShop\Settings\SettingDefinition;
use PnShop\Settings\SettingsRegistry;
use PnShop\Settings\SettingsSchema;
use PnShop\Settings\SettingType;

/**
 * Sales module: orders, checkout, payments.
 */
class SalesServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OrderWorkflow::class);

        Relation::morphMap(['order' => Order::class]);
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

        $this->app->make(SettingsRegistry::class)->register(new SettingsSchema(
            'sales',
            'Orders',
            new SettingDefinition('order_number_prefix', SettingType::String, 'Order number prefix', default: 'ORD-', help: 'Applies to new orders, e.g. ORD-000042.', rules: ['max:12']),
            new SettingDefinition('order_number_digits', SettingType::Integer, 'Order number digits', default: 6, required: true, help: 'The order id is padded with zeros to this length.', rules: ['min:1', 'max:12']),
        ));
    }
}
