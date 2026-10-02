<?php

namespace PnShop\Installer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PnShop\Foundation\PnShop;
use Throwable;

/**
 * Whether this copy of PN Shop has been installed, and its version history.
 *
 * Installed means: the lock file exists (fast path, no database), or the database records
 * an install (system_versions) or has staff accounts. Finding it in the database writes
 * the lock file, so the check stays cheap afterwards.
 */
final class Installation
{
    private ?bool $installed = null;

    public static function lockPath(): string
    {
        return (string) config('pnshop.installer.lock', storage_path('app/pnshop-installed.json'));
    }

    public const INSTALLED = 'installed';

    public const FRESH = 'fresh';

    /** The database cannot be reached, so whether the shop is installed is not known. */
    public const UNKNOWN = 'unknown';

    public function isInstalled(): bool
    {
        return $this->state() === self::INSTALLED;
    }

    /**
     * installed, fresh (a reachable empty database, or no SQLite file yet), or unknown (the
     * database cannot be reached). Unknown must never open the installer: an outage on a
     * live shop would otherwise let anyone re-run the installation.
     */
    public function state(): string
    {
        if ($this->installed === true || is_file(self::lockPath())) {
            $this->installed = true;

            return self::INSTALLED;
        }

        try {
            $recorded = (Schema::hasTable('system_versions') && DB::table('system_versions')->exists())
                || (Schema::hasTable('admin_users') && DB::table('admin_users')->exists());
        } catch (Throwable) {
            return $this->sqliteFileIsNew() ? self::FRESH : self::UNKNOWN;
        }

        if ($recorded) {
            $this->writeLock($this->installedVersion() ?? PnShop::VERSION);
            $this->installed = true;

            return self::INSTALLED;
        }

        return self::FRESH;
    }

    private function sqliteFileIsNew(): bool
    {
        $connection = (string) config('database.default');

        if (config("database.connections.{$connection}.driver") !== 'sqlite') {
            return false;
        }

        $file = (string) config("database.connections.{$connection}.database");

        return $file !== ':memory:' && (! is_file($file) || filesize($file) === 0);
    }

    /**
     * The version the database was last installed or updated to.
     */
    public function installedVersion(): ?string
    {
        try {
            if (! Schema::hasTable('system_versions')) {
                return null;
            }

            $version = DB::table('system_versions')->orderByDesc('id')->value('version');

            return is_string($version) ? $version : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Record an install or update and (re)write the lock file.
     *
     * @param  array<string, mixed>  $details
     */
    public function record(string $action, ?string $from = null, array $details = []): void
    {
        DB::table('system_versions')->insert([
            'version' => PnShop::VERSION,
            'from_version' => $from,
            'action' => $action,
            'details' => $details === [] ? null : json_encode($details),
            'created_at' => now(),
        ]);

        $this->writeLock(PnShop::VERSION);
        $this->installed = true;
    }

    /**
     * @return list<array{version: string, from_version: string|null, action: string, created_at: string|null}>
     */
    public function history(): array
    {
        try {
            return array_values(DB::table('system_versions')->orderByDesc('id')->get(['version', 'from_version', 'action', 'created_at'])
                ->map(fn (object $row) => [
                    'version' => (string) $row->version,
                    'from_version' => isset($row->from_version) ? (string) $row->from_version : null,
                    'action' => (string) $row->action,
                    'created_at' => isset($row->created_at) ? (string) $row->created_at : null,
                ])
                ->all());
        } catch (Throwable) {
            return [];
        }
    }

    public function forget(): void
    {
        $this->installed = null;
    }

    private function writeLock(string $version): void
    {
        File::ensureDirectoryExists(dirname(self::lockPath()));
        File::put(self::lockPath(), (string) json_encode(['version' => $version, 'at' => now()->toIso8601String()], JSON_PRETTY_PRINT));
    }
}
