<?php

namespace PnShop\Extension;

use Illuminate\Support\Facades\View;
use PnShop\Extension\Console\PluginCommand;
use PnShop\Extension\Console\PluginKeygenCommand;
use PnShop\Extension\Console\PluginListCommand;
use PnShop\Extension\Console\PluginSignCommand;
use PnShop\Foundation\Extension\Permission;
use PnShop\Foundation\ModuleServiceProvider;

/**
 * The extension manager: discovery, lifecycle, integrity, commands and the admin page.
 */
class ExtensionServiceProvider extends ModuleServiceProvider
{
    protected function permissions(): array
    {
        return [
            new Permission('system.extensions.manage', 'Install, enable and remove extensions (runs third-party code)', 'System'),
        ];
    }

    protected function bootModule(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PluginListCommand::class, PluginCommand::class, PluginSignCommand::class, PluginKeygenCommand::class]);
        }

        // Plugins' storefront scripts, loaded by the root template after the app.
        View::composer('pnshop::app', fn ($view) => $view->with('pluginScripts', PluginLoader::storefrontScripts()));
    }
}
