<?php

namespace PnShop\Installer;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use PnShop\Foundation\ModuleServiceProvider;
use PnShop\Foundation\PnShop;
use PnShop\Installer\Backup\LocalBackup;
use PnShop\Installer\Console\InstallCommand;
use PnShop\Installer\Console\UpdateCommand;
use PnShop\Installer\Contracts\BackupDriver;
use PnShop\Installer\Http\Controllers\InstallerController;
use PnShop\Installer\Http\Middleware\InstallGate;

/**
 * Installing (pnshop:install, the web installer at /install) and updating (pnshop:update)
 * PN Shop, with the version history in system_versions.
 */
class InstallerServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Installation::class);
        $this->app->bindIf(BackupDriver::class, LocalBackup::class);
    }

    protected function bootModule(): void
    {
        $this->loadViewsFrom($this->modulePath('resources/views'), 'pnshop-installer');

        // First of all global middleware: nothing that needs the database runs before it.
        $this->app->make(HttpKernel::class)->prependMiddleware(InstallGate::class);

        if (! $this->app->routesAreCached()) {
            // Not the "web" group: no Inertia, no database sessions (the database may not exist yet).
            Route::middleware([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class, ShareErrorsFromSession::class, ValidateCsrfToken::class, SubstituteBindings::class])
                ->prefix('install')
                ->name('install.')
                ->group(function (): void {
                    Route::get('/', [InstallerController::class, 'requirements'])->name('requirements');
                    Route::get('database', [InstallerController::class, 'database'])->name('database');
                    Route::post('database', [InstallerController::class, 'saveDatabase'])->middleware('throttle:20,1')->name('database.save');
                    Route::get('store', [InstallerController::class, 'store'])->name('store');
                    Route::post('store', [InstallerController::class, 'install'])->middleware('throttle:5,1')->name('install');
                });
        }

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class, UpdateCommand::class]);
        }

        AboutCommand::add('PN Shop', fn () => [
            'Version' => PnShop::VERSION,
            'Database version' => $this->app->make(Installation::class)->installedVersion() ?? 'not installed',
        ]);
    }
}
