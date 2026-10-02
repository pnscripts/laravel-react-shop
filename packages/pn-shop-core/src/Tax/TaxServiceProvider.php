<?php

namespace PnShop\Tax;

use Illuminate\Support\Facades\Gate;
use PnShop\Cart\Totals\CartCalculator;
use PnShop\Foundation\Extension\Permission;
use PnShop\Foundation\Extension\PipelineRegistry;
use PnShop\Foundation\ModuleServiceProvider;
use PnShop\Settings\SettingDefinition;
use PnShop\Settings\SettingsRegistry;
use PnShop\Settings\SettingsSchema;
use PnShop\Settings\SettingType;
use PnShop\Tax\Contracts\TaxProvider;
use PnShop\Tax\Models\TaxClass;
use PnShop\Tax\Models\TaxRate;
use PnShop\Tax\Models\TaxZone;
use PnShop\Tax\Policies\TaxPolicy;

/**
 * Tax classes, zones and rates; the replaceable TaxProvider.
 */
class TaxServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bindIf(TaxProvider::class, TableTaxProvider::class);
    }

    protected function permissions(): array
    {
        return [
            new Permission('store.tax.manage', 'Manage tax classes, zones and rates', 'Store'),
        ];
    }

    protected function bootModule(): void
    {
        foreach ([TaxClass::class, TaxZone::class, TaxRate::class] as $model) {
            Gate::policy($model, TaxPolicy::class);
        }

        $this->app->make(SettingsRegistry::class)->register(new SettingsSchema(
            'tax',
            'Tax',
            new SettingDefinition('prices_include_tax', SettingType::Boolean, 'Prices include tax', default: true, help: 'On: catalog prices are what customers pay and tax is shown as included (usual for VAT). Off: tax is added at checkout.'),
            new SettingDefinition('based_on', SettingType::Select, 'Calculate tax for', default: 'shipping', required: true, options: [
                'shipping' => 'The shipping address',
                'billing' => 'The billing address',
                'store' => 'The store country',
            ]),
            new SettingDefinition('store_country', SettingType::String, 'Store country', help: 'Two-letter code, e.g. BG. Used before the customer enters an address, and for "store country".', rules: ['size:2', 'alpha']),
        ));

        $this->app->make(PipelineRegistry::class)->stage(CartCalculator::PIPELINE, ApplyTax::class, ApplyTax::PRIORITY);
    }
}
