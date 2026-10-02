<?php

namespace PnShop\Extension;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use PnShop\Settings\Settings;

/**
 * Base class of a plugin's provider (the "provider" in pnshop.json).
 *
 * It is a Laravel service provider: register services in register(), and use bootPlugin()
 * for the rest (gateways, carriers, blocks, pipeline stages, listeners). These are loaded
 * automatically when present in the plugin folder:
 *
 * - routes/web.php (web middleware, localized like the shop's routes)
 * - resources/views (as "<vendor_name>::view")
 * - lang (as "<vendor_name>::file.key", and JSON translations)
 * - src/Filament/{Resources,Pages,Widgets} (admin screens)
 *
 * Migrations in database/migrations are run by the extension manager when the plugin is
 * installed or updated, not on every boot.
 */
abstract class Plugin extends ServiceProvider
{
    private ?Manifest $manifest = null;

    public function setManifest(Manifest $manifest): void
    {
        $this->manifest = $manifest;
    }

    public function manifest(): Manifest
    {
        return $this->manifest ?? app(PluginLoader::class)->manifestFor(static::class)
            ?? throw new \LogicException(static::class.' was not loaded by the extension manager.');
    }

    /** Called once after the plugin's migrations ran on install. */
    public function install(): void {}

    /** Called after a newer version's migrations ran. */
    public function upgrade(string $from, string $to): void {}

    /**
     * Called before the plugin is removed. With $keepData false its migrations are rolled
     * back afterwards and its settings deleted; remove any other data here.
     */
    public function uninstall(bool $keepData): void {}

    public function boot(): void
    {
        $manifest = $this->manifest();
        $key = $manifest->key();

        if (is_dir($views = $this->path('resources/views'))) {
            $this->loadViewsFrom($views, $key);
        }

        if (is_dir($lang = $this->path('lang'))) {
            $this->loadTranslationsFrom($lang, $key);
            $this->loadJsonTranslationsFrom($lang);
        }

        if (is_file($routes = $this->path('routes/web.php')) && ! $this->app->routesAreCached()) {
            Route::middleware('web')->group($routes);

            // Plugins enabled while the app is running add routes after the name index was built.
            Route::getRoutes()->refreshNameLookups();
            Route::getRoutes()->refreshActionLookups();
        }

        $this->bootPlugin();
    }

    protected function bootPlugin(): void {}

    protected function path(string $relative = ''): string
    {
        return $this->manifest()->path.($relative === '' ? '' : '/'.ltrim($relative, '/'));
    }

    /** One of the plugin's settings (declared in pnshop.json). */
    protected function setting(string $key): mixed
    {
        return $this->app->make(Settings::class)->get('plugin.'.$this->manifest()->key().'.'.$key);
    }
}
