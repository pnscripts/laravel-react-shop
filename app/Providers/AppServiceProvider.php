<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * The shop's own services. PN Shop itself is registered by pnscripts/pn-shop-core.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    public function boot(): void
    {
        //
    }
}
