<?php

namespace PnShop\Extension\Console;

use Illuminate\Console\Command;
use PnShop\Extension\Exceptions\ExtensionException;
use PnShop\Extension\ExtensionManager;

/**
 * php artisan pnshop:plugin install|enable|disable|update|uninstall|verify <vendor/name>
 */
class PluginCommand extends Command
{
    protected $signature = 'pnshop:plugin
        {action : install, enable, disable, update, uninstall or verify}
        {id : The plugin id, vendor/name}
        {--purge : With uninstall: also roll back its migrations and delete its settings}
        {--force : Skip the confirmation}';

    protected $description = 'Install, enable, disable, update, uninstall or verify a plugin';

    public function handle(ExtensionManager $manager): int
    {
        $id = (string) $this->argument('id');
        $action = (string) $this->argument('action');

        try {
            match ($action) {
                'install' => $manager->install($id),
                'enable' => $manager->enable($id),
                'disable' => $manager->disable($id),
                'update' => $manager->update($id),
                'uninstall' => $this->uninstall($manager, $id),
                'verify' => $this->verify($manager, $id),
                default => throw new ExtensionException("Unknown action [{$action}]."),
            };
        } catch (ExtensionException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($action !== 'verify') {
            $this->info(ucfirst($action)." {$id}: done.");
        }

        return self::SUCCESS;
    }

    private function uninstall(ExtensionManager $manager, string $id): void
    {
        $purge = (bool) $this->option('purge');

        if ($purge && ! $this->option('force') && ! $this->confirm("Delete all data of {$id}? This cannot be undone.")) {
            throw new ExtensionException('Cancelled.');
        }

        $manager->uninstall($id, keepData: ! $purge);
    }

    private function verify(ExtensionManager $manager, string $id): void
    {
        $changes = $manager->verify($id);

        if (array_merge(...array_values($changes)) === []) {
            $this->info("{$id}: no files changed since it was installed.");

            return;
        }

        foreach ($changes as $kind => $files) {
            foreach ($files as $file) {
                $this->warn("{$kind}: {$file}");
            }
        }

        throw new ExtensionException("{$id}: files changed since it was installed.");
    }
}
