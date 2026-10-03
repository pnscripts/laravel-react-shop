<?php

namespace PnShop\Foundation;

use Illuminate\Contracts\Foundation\CachesConfiguration;
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
        $this->mergeDefaults();

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

    /**
     * The core's config/pnshop.php under the shop's own: the shop's values win, at any depth,
     * so a default added by an update (for example a new security option) reaches shops whose
     * config/pnshop.php is a full copy of an older version. Lists are replaced whole.
     */
    private function mergeDefaults(): void
    {
        if ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached()) {
            return;
        }

        $config = $this->app->make('config');

        /** @var array<string, mixed> $defaults */
        $defaults = require PnShop::path('config/pnshop.php');

        $config->set('pnshop', self::mergeConfig($defaults, (array) $config->get('pnshop', [])));
    }

    /**
     * @param  array<mixed>  $defaults
     * @param  array<mixed>  $values
     * @return array<mixed>
     */
    public static function mergeConfig(array $defaults, array $values): array
    {
        foreach ($values as $key => $value) {
            $defaults[$key] = is_array($value) && ! array_is_list($value) && is_array($defaults[$key] ?? null) && ! array_is_list($defaults[$key])
                ? self::mergeConfig($defaults[$key], $value)
                : $value;
        }

        return $defaults;
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
