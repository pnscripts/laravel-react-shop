<?php

namespace PnShop\Settings;

use PnShop\Foundation\Extension\Permission;
use PnShop\Foundation\ModuleServiceProvider;

class SettingsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRegistry::class);
        $this->app->singleton(Settings::class);
    }

    protected function permissions(): array
    {
        return [
            new Permission('system.settings.manage', 'Manage settings', 'System'),
        ];
    }

    protected function bootModule(): void
    {
        $this->app->make(SettingsRegistry::class)->register(new SettingsSchema(
            'store',
            'Store',
            new SettingDefinition('name', SettingType::String, 'Store name', default: config('app.name'), required: true),
            new SettingDefinition('email', SettingType::Email, 'Contact email'),
            new SettingDefinition('phone', SettingType::String, 'Contact phone'),
            new SettingDefinition('address', SettingType::Text, 'Store address'),
        ));
    }
}
