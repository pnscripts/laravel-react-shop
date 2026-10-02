<?php

namespace PnShop\Returns;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use PnShop\Foundation\Extension\Permission;
use PnShop\Foundation\ModuleServiceProvider;
use PnShop\Returns\Models\ReturnRequest;
use PnShop\Returns\Policies\ReturnRequestPolicy;
use PnShop\Settings\SettingDefinition;
use PnShop\Settings\SettingsRegistry;
use PnShop\Settings\SettingsSchema;
use PnShop\Settings\SettingType;

/**
 * Return requests (RMA): customers request returns of shipped items, staff approve,
 * receive, restock and refund them.
 */
class ReturnsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        Relation::morphMap(['return_request' => ReturnRequest::class]);
    }

    protected function permissions(): array
    {
        return [new Permission('sales.returns.manage', 'Handle return requests', 'Sales')];
    }

    protected function bootModule(): void
    {
        $this->app->make(SettingsRegistry::class)->register(new SettingsSchema(
            'returns',
            'Returns',
            new SettingDefinition('enabled', SettingType::Boolean, 'Customers can request returns online', default: true),
            new SettingDefinition('window_days', SettingType::Integer, 'Return period (days after shipping)', default: 14, required: true, rules: ['min:0', 'max:365'], help: 'In the EU consumers have at least 14 days to withdraw from an online purchase.'),
            new SettingDefinition('number_prefix', SettingType::String, 'Return number prefix', default: 'RMA-', rules: ['max:12']),
        ));

        Gate::policy(ReturnRequest::class, ReturnRequestPolicy::class);
    }
}
