<?php

namespace PnShop\Theme\Console;

use Illuminate\Console\Command;
use PnShop\Extension\Exceptions\ExtensionException;
use PnShop\Theme\ThemeManager;
use PnShop\Theme\ThemeManifest;

/**
 * php artisan pnshop:theme list|activate|publish [vendor/name]
 */
class ThemeCommand extends Command
{
    protected $signature = 'pnshop:theme {action : list, activate or publish} {id? : The theme id, vendor/name}';

    protected $description = 'List, activate or republish storefront themes';

    public function handle(ThemeManager $themes): int
    {
        $action = (string) $this->argument('action');
        $id = (string) $this->argument('id');

        try {
            match ($action) {
                'list' => $this->list($themes),
                'activate' => $this->info('Activated '.$themes->activate($id)->id.'.'),
                'publish' => $this->publish($themes, $id),
                default => throw new ExtensionException("Unknown action [{$action}]."),
            };
        } catch (ExtensionException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function list(ThemeManager $themes): void
    {
        $active = $themes->active()->id;
        $invalid = [];

        $this->table(['Theme', 'Version', 'Extends', 'State'], $themes->discover($invalid)->map(fn (ThemeManifest $theme) => [
            $theme->id,
            $theme->version,
            $theme->parent ?? '—',
            $theme->id === $active ? 'active' : (implode(' ', $themes->problems($theme)) ?: 'ready'),
        ])->values()->all());

        foreach ($invalid as $path => $error) {
            $this->error("{$path}: {$error}");
        }
    }

    private function publish(ThemeManager $themes, string $id): void
    {
        $themes->publish($themes->find($id));
        $this->info("Published {$id}.");
    }
}
