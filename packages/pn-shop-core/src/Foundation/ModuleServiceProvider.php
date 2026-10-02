<?php

namespace PnShop\Foundation;

use Illuminate\Support\ServiceProvider;
use PnShop\Foundation\Extension\Permission;
use PnShop\Foundation\Extension\PermissionRegistry;
use ReflectionClass;

/**
 * Base class for core modules. A module lives in core/<Module>/ and may ship
 * database/migrations and declare permissions.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Permissions this module contributes to the permission registry.
     *
     * @return list<Permission>
     */
    protected function permissions(): array
    {
        return [];
    }

    public function boot(): void
    {
        $migrations = $this->modulePath('database/migrations');

        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }

        $this->app->make(PermissionRegistry::class)->register(...$this->permissions());

        $this->bootModule();
    }

    protected function bootModule(): void
    {
        //
    }

    protected function modulePath(string $path = ''): string
    {
        $directory = dirname((string) (new ReflectionClass(static::class))->getFileName());

        return $path === '' ? $directory : $directory.'/'.$path;
    }
}
