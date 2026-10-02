<?php

namespace PnShop\Acl\Console;

use Illuminate\Console\Command;
use PnShop\Acl\PermissionSynchronizer;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'pnshop:permissions:sync';

    protected $description = 'Store newly registered permissions and make sure the built-in staff roles exist';

    public function handle(PermissionSynchronizer $synchronizer): int
    {
        $created = $synchronizer->sync();

        $this->info($created === [] ? 'Permissions are up to date.' : 'Created '.count($created).' permission(s).');

        return self::SUCCESS;
    }
}
