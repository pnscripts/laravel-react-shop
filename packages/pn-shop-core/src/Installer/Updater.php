<?php

namespace PnShop\Installer;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PnShop\Cms\Menus;
use PnShop\Extension\ExtensionManager;
use PnShop\Extension\ExtensionStatus;
use PnShop\Extension\Models\Extension;
use PnShop\Foundation\PnShop;
use PnShop\Installer\Contracts\BackupDriver;
use PnShop\Localization\Localization;
use PnShop\Settings\Settings;
use PnShop\Theme\ThemeManager;
use RuntimeException;
use Throwable;

/**
 * Brings the database and caches up to the code after new PN Shop files were put in place
 * (composer update, or a release archive): checks, backup, maintenance mode, core and
 * plugin migrations, cache rebuild, smoke checks, and a version record.
 */
class Updater
{
    public function __construct(
        private Application $app,
        private Installation $installation,
        private Requirements $requirements,
        private ExtensionManager $extensions,
        private BackupDriver $backup,
    ) {}

    /**
     * What an update would do, without changing anything.
     *
     * @return array{from: string|null, to: string, requirements: list<string>, migrations: list<string>, incompatible: array<string, list<string>>, plugin_updates: array<string, string>}
     */
    public function plan(): array
    {
        $failed = array_values(array_map(
            fn (array $check) => $check['label'].' — '.$check['detail'],
            array_filter($this->requirements->check(), fn (array $check) => $check['required'] && ! $check['ok']),
        ));

        $incompatible = [];
        $updates = [];

        foreach (Extension::query()->where('status', ExtensionStatus::Enabled)->get() as $extension) {
            try {
                $manifest = $this->extensions->find($extension->id);
            } catch (Throwable $e) {
                $incompatible[$extension->id] = [$e->getMessage()];

                continue;
            }

            $problems = $this->extensions->problems($manifest);

            if ($problems !== []) {
                $incompatible[$extension->id] = $problems;
            } elseif ($manifest->version !== $extension->version) {
                $updates[$extension->id] = "{$extension->version} → {$manifest->version}";
            }
        }

        return [
            'from' => $this->installation->installedVersion(),
            'to' => PnShop::VERSION,
            'requirements' => $failed,
            'migrations' => $this->pendingMigrations(),
            'incompatible' => $incompatible,
            'plugin_updates' => $updates,
        ];
    }

    /**
     * @param  (Closure(string): void)|null  $progress
     * @return array<string, mixed> the plan that was carried out, plus the backup location
     *
     * @throws RuntimeException when a check fails; the shop is left as it was (and back online)
     */
    public function run(bool $backup = true, bool $withFiles = false, bool $disableIncompatible = false, ?Closure $progress = null): array
    {
        $step = $progress ?? fn (string $message) => null;
        $plan = $this->plan();

        if ($plan['requirements'] !== []) {
            throw new RuntimeException('Server requirements are not met: '.implode('; ', $plan['requirements']));
        }

        if ($plan['incompatible'] !== [] && ! $disableIncompatible) {
            throw new RuntimeException('Some enabled plugins do not work with PN Shop '.PnShop::VERSION.': '.implode(', ', array_keys($plan['incompatible'])).'. Update them first, or run with --disable-incompatible.');
        }

        if ($backup) {
            $step('Backing up');
            $plan['backup'] = $this->backup->backup('pnshop-'.($plan['from'] ?? 'unknown').'-to-'.PnShop::VERSION, $withFiles);
        }

        $wasDown = $this->app->isDownForMaintenance();

        if (! $wasDown) {
            $step('Maintenance mode on');
            Artisan::call('down', ['--retry' => 60]);
        }

        try {
            foreach (array_keys($plan['incompatible']) as $id) {
                $step("Disabling incompatible plugin {$id}");
                $this->extensions->disable($id);
            }

            $step('Updating the database');
            Artisan::call('migrate', ['--force' => true]);

            foreach (array_keys($plan['plugin_updates']) as $id) {
                $step("Updating plugin {$id}");
                $this->extensions->update($id);
            }

            $step('Rebuilding caches');
            $this->rebuildCaches();

            $step('Checking the shop');
            $this->smokeCheck();

            $this->installation->record('update', $plan['from'], array_filter([
                'migrations' => count($plan['migrations']),
                'plugins_updated' => array_keys($plan['plugin_updates']),
                'plugins_disabled' => array_keys($plan['incompatible']),
            ]));
        } finally {
            if (! $wasDown) {
                Artisan::call('up');
                $step('Maintenance mode off');
            }
        }

        return $plan;
    }

    private function rebuildCaches(): void
    {
        Artisan::call('optimize:clear');

        app(Settings::class)->flush();
        app(Localization::class)->flush();
        Menus::flush();

        $this->extensions->rebuildCache();

        $themes = app(ThemeManager::class);
        $themes->publish($themes->builtin());
        $active = $themes->active();

        if (! $active->builtin) {
            $themes->publish($active);
        }

        if (! is_link(public_path('storage')) && ! is_dir(public_path('storage'))) {
            Artisan::call('storage:link');
        }
    }

    /**
     * The database answers, the core tables exist and the settings load.
     */
    private function smokeCheck(): void
    {
        DB::connection()->getPdo();

        foreach (['system_versions', 'products', 'orders', 'settings', 'admin_users'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("The table {$table} is missing after the update.");
            }
        }

        app(Settings::class)->get('store.name');
        app(Localization::class)->defaultCurrency();
    }

    /**
     * @return list<string>
     */
    private function pendingMigrations(): array
    {
        /** @var Migrator $migrator */
        $migrator = $this->app->make('migrator');

        if (! $migrator->repositoryExists()) {
            return ['(all — the database has no tables yet)'];
        }

        $ran = $migrator->getRepository()->getRan();
        $files = $migrator->getMigrationFiles(array_merge([$this->app->databasePath('migrations')], $migrator->paths()));

        return array_values(array_diff(array_keys($files), $ran));
    }
}
