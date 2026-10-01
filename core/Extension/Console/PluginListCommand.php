<?php

namespace PnShop\Extension\Console;

use Illuminate\Console\Command;
use PnShop\Extension\ExtensionManager;
use PnShop\Extension\Manifest;
use PnShop\Extension\PluginLoader;

class PluginListCommand extends Command
{
    protected $signature = 'pnshop:plugin:list';

    protected $description = 'List the plugins found on disk and their state';

    public function handle(ExtensionManager $manager): int
    {
        if (PluginLoader::safeMode()) {
            $this->warn('Safe mode is on: no plugin is booted.');
        }

        $invalid = [];
        $rows = $manager->discover($invalid)->map(fn (Manifest $manifest) => [
            $manifest->id,
            $manifest->version,
            $manager->status($manifest->id)->value,
            implode(' ', $manager->problems($manifest)),
        ])->values()->all();

        $this->table(['Plugin', 'Version', 'Status', 'Problems'], $rows);

        foreach ($invalid as $path => $error) {
            $this->error("{$path}: {$error}");
        }

        return self::SUCCESS;
    }
}
