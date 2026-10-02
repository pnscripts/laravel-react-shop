<?php

namespace PnShop\Installer\Backup;

use Illuminate\Support\Facades\File;
use PnShop\Installer\Contracts\BackupDriver;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Backups in storage/app/backups/<date>-<label>/: the database (an SQLite copy, or a
 * mysqldump / pg_dump), .env, and optionally storage/app as a zip. The folder is readable
 * by the owner only, since it holds credentials.
 */
class LocalBackup implements BackupDriver
{
    public function backup(string $label, bool $withFiles = false): string
    {
        $directory = storage_path('app/backups/'.now()->format('Ymd-His').'-'.preg_replace('/[^A-Za-z0-9._-]/', '-', $label));
        File::ensureDirectoryExists($directory, 0700);

        $this->database($directory);

        if (is_file(app()->environmentFilePath())) {
            File::copy(app()->environmentFilePath(), $directory.'/env');
            @chmod($directory.'/env', 0600);
        }

        if ($withFiles) {
            $this->files($directory.'/storage-app.zip');
        }

        return $directory;
    }

    private function database(string $directory): void
    {
        $connection = (string) config('database.default');
        /** @var array<string, mixed> $config */
        $config = (array) config("database.connections.{$connection}");
        $driver = (string) ($config['driver'] ?? '');

        if ($driver === 'sqlite') {
            $file = (string) ($config['database'] ?? '');

            if ($file === '' || $file === ':memory:' || ! is_file($file)) {
                throw new RuntimeException('The SQLite database file was not found.');
            }

            File::copy($file, $directory.'/database.sqlite');
            @chmod($directory.'/database.sqlite', 0600);

            return;
        }

        [$command, $env] = match ($driver) {
            'mysql', 'mariadb' => [[
                'mysqldump', '--single-transaction', '--routines', '--no-tablespaces',
                '--host='.($config['host'] ?? '127.0.0.1'), '--port='.($config['port'] ?? 3306),
                '--user='.($config['username'] ?? ''), '--result-file='.$directory.'/database.sql',
                ...(! empty($config['unix_socket']) ? ['--socket='.$config['unix_socket']] : []),
                (string) ($config['database'] ?? ''),
            ], ['MYSQL_PWD' => (string) ($config['password'] ?? '')]],
            'pgsql' => [[
                'pg_dump', '--no-owner', '--host='.($config['host'] ?? '127.0.0.1'), '--port='.($config['port'] ?? 5432),
                '--username='.($config['username'] ?? ''), '--file='.$directory.'/database.sql', (string) ($config['database'] ?? ''),
            ], ['PGPASSWORD' => (string) ($config['password'] ?? '')]],
            default => throw new RuntimeException("Backups of {$driver} databases are not supported; make one yourself and run the update with --no-backup."),
        };

        // Passwords go through the environment, never the command line (visible in process lists).
        $process = new Process($command, base_path(), $env, timeout: 900);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('The database backup failed: '.trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        @chmod($directory.'/database.sql', 0600);
    }

    private function files(string $zipPath): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Backing up files needs the PHP zip extension.');
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not create {$zipPath}.");
        }

        $root = storage_path('app');

        foreach (File::allFiles($root) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());

            if (! str_starts_with($relative, 'backups/')) {
                $zip->addFile($file->getPathname(), $relative);
            }
        }

        $zip->close();
        @chmod($zipPath, 0600);
    }
}
