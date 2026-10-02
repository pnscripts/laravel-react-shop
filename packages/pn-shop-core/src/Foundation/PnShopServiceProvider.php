<?php

namespace PnShop\Foundation;

use Illuminate\Support\ServiceProvider;
use PnShop\Extension\PluginLoader;
use PnShop\Foundation\Extension\PermissionRegistry;
use PnShop\Foundation\Extension\PipelineRegistry;
use PnShop\Theme\ThemeManifest;

/**
 * Boots the PN Shop core: the extension kernel and every core module.
 */
class PnShopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Defaults for config/pnshop.php; the shop's own copy overrides them key by key.
        $this->mergeConfigFrom(PnShop::path('config/pnshop.php'), 'pnshop');

        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(PipelineRegistry::class);

        /** @var list<class-string<ServiceProvider>> $extra */
        $extra = (array) config('pnshop.extra_modules', []);

        foreach ([...PnShop::MODULES, ...$extra] as $module) {
            $this->app->register($module);
        }

        // Enabled plugins come after the core modules whose registries they use.
        $this->app->singleton(PluginLoader::class);
        $this->app->make(PluginLoader::class)->bootEnabled();
    }

    public function boot(): void
    {
        // The base tables (customers, cache, queue, the original catalog and orders) and the
        // shared views and interface strings of the core.
        $this->loadMigrationsFrom(PnShop::path('database/migrations'));
        $this->loadViewsFrom(PnShop::path('resources/views'), 'pnshop');
        $this->loadJsonTranslationsFrom(PnShop::path('lang'));

        // The prebuilt storefront; composer update publishes it with the laravel-assets tag.
        if ($this->app->runningInConsole() && is_dir(PnShop::path('theme/dist'))) {
            $this->publishes([PnShop::path('theme/dist') => public_path(ThemeManifest::CORE_BUILD)], ['pnshop-assets', 'laravel-assets']);
        }
    }
}
