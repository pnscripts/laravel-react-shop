<?php

namespace PnShop\Foundation;

use Illuminate\Support\ServiceProvider;
use PnShop\Foundation\Extension\PermissionRegistry;
use PnShop\Foundation\Extension\PipelineRegistry;

/**
 * Boots the PN Shop core: the extension kernel and every core module.
 */
class PnShopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionRegistry::class);
        $this->app->singleton(PipelineRegistry::class);

        /** @var list<class-string<ServiceProvider>> $modules */
        $modules = config('pnshop.modules', []);

        foreach ($modules as $module) {
            $this->app->register($module);
        }
    }
}
