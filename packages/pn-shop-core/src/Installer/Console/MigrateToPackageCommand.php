<?php

namespace PnShop\Installer\Console;

use Illuminate\Console\Command;
use PnShop\Foundation\PnShop;
use PnShop\Installer\PackageMigration;
use Throwable;

class MigrateToPackageCommand extends Command
{
    protected $signature = 'pnshop:migrate-to-package
        {--dry-run : Only show what would change}
        {--leave-repository : In a git clone of PN Shop: switch to the published package anyway}
        {--force : Do not ask for confirmation}';

    protected $description = 'Move this shop onto the published pnscripts/pn-shop-core package and set aside the files PN Shop 1.0 kept in the project (the database is not touched)';

    public function handle(): int
    {
        $migration = new PackageMigration(base_path(), leaveRepository: (bool) $this->option('leave-repository'));

        try {
            $plan = $migration->plan();
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $changed = array_keys(array_filter($plan['leftovers']));

        $this->list('composer.json', $plan['composer']);
        $this->list('Files from PN Shop 1.0 to set aside', count($plan['leftovers']) > 0 ? [count($plan['leftovers']).' files (core/, app/Http, resources/js, …)'] : []);
        $this->list('Of these, changed since 1.0 (copy your changes over to your own code)', $changed, warn: true);
        $this->list('The unused copy of the core', $plan['local_package'] ? [PackageMigration::LOCAL_PACKAGE] : []);
        $this->list('Fix these in your files', $plan['problems'], warn: true);

        if ($plan['composer'] === [] && $plan['leftovers'] === [] && ! $plan['local_package']) {
            $this->info('Nothing to move.'.($plan['problems'] === [] ? ' The shop already uses the package.' : ''));

            return $plan['problems'] === [] ? self::SUCCESS : self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run: nothing was changed.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && $this->input->isInteractive() && ! $this->confirm('Make these changes? Files are moved, not deleted.', true)) {
            return self::FAILURE;
        }

        $target = $migration->run(now()->format('Y-m-d-His'));

        if ($target !== null) {
            $this->line("Moved files are in {$target}".($plan['composer'] !== [] ? ', with a copy of the old composer.json.' : '.'));
        }

        if ($plan['composer'] !== []) {
            $this->newLine();
            $this->info('Next: composer update '.PnShop::PACKAGE.' --with-all-dependencies && php artisan pnshop:update');
            $this->line('Run this command once more afterwards: it sets aside '.PackageMigration::LOCAL_PACKAGE.' when Composer no longer loads the core from it.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $items
     */
    private function list(string $title, array $items, bool $warn = false): void
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
