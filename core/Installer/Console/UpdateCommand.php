<?php

namespace PnShop\Installer\Console;

use Illuminate\Console\Command;
use PnShop\Installer\Installation;
use PnShop\Installer\Updater;
use Throwable;

class UpdateCommand extends Command
{
    protected $signature = 'pnshop:update
        {--dry-run : Only show what the update would do}
        {--no-backup : Skip the backup (make your own first)}
        {--with-files : Also back up storage/app (uploads)}
        {--disable-incompatible : Disable enabled plugins that do not support this version}
        {--force : Do not ask for confirmation}';

    protected $description = 'Finish an update of PN Shop after the new code is in place: backup, migrations, plugin updates, caches';

    public function handle(Updater $updater, Installation $installation): int
    {
        if (! $installation->isInstalled()) {
            $this->error('PN Shop is not installed yet. Run `php artisan pnshop:install`.');

            return self::FAILURE;
        }

        $plan = $updater->plan();

        $this->line("Database version: <info>{$plan['from']}</info>   Code version: <info>{$plan['to']}</info>");
        $this->report('Pending migrations', $plan['migrations']);
        $this->report('Plugins to update', array_map(fn (string $id, string $change) => "{$id} {$change}", array_keys($plan['plugin_updates']), $plan['plugin_updates']));
        $this->report('Incompatible plugins', array_map(fn (string $id, array $problems) => "{$id}: ".implode(' ', $problems), array_keys($plan['incompatible']), $plan['incompatible']), warn: true);
        $this->report('Requirements not met', $plan['requirements'], warn: true);

        if ($this->option('dry-run')) {
            $this->info('Dry run: nothing was changed.');

            return $plan['requirements'] === [] && ($plan['incompatible'] === [] || $this->option('disable-incompatible')) ? self::SUCCESS : self::FAILURE;
        }

        if (! $this->option('force') && $this->input->isInteractive() && ! $this->confirm('Run the update now? The shop is in maintenance mode meanwhile.', true)) {
            return self::FAILURE;
        }

        try {
            $result = $updater->run(
                backup: ! $this->option('no-backup'),
                withFiles: (bool) $this->option('with-files'),
                disableIncompatible: (bool) $this->option('disable-incompatible'),
                progress: fn (string $step) => $this->line("  · {$step}"),
            );
        } catch (Throwable $e) {
            $this->error('Update failed: '.$e->getMessage());
            $this->line('The shop is back online. If the database was changed, restore it from the backup.');

            return self::FAILURE;
        }

        if (isset($result['backup'])) {
            $this->line("Backup: {$result['backup']}");
        }

        $this->info("PN Shop is up to date ({$result['to']}).");

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $items
     */
    private function report(string $title, array $items, bool $warn = false): void
    {
        if ($items === []) {
            return;
        }

        $this->newLine();
        $warn ? $this->warn("{$title}:") : $this->line("<comment>{$title}:</comment>");

        foreach ($items as $item) {
            $this->line("  - {$item}");
        }
    }
}
