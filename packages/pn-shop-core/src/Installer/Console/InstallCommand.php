<?php

namespace PnShop\Installer\Console;

use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PnShop\Installer\EnvironmentFile;
use PnShop\Installer\Installation;
use PnShop\Installer\Installer;
use PnShop\Installer\InstallOptions;
use PnShop\Installer\Requirements;
use Symfony\Component\Process\Process;
use Throwable;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class InstallCommand extends Command
{
    protected $signature = 'pnshop:install
        {--db-connection= : sqlite, mysql, mariadb or pgsql (keeps .env when omitted)}
        {--db-host=} {--db-port=} {--db-database=} {--db-username=} {--db-password=}
        {--app-url= : The shop\'s address, e.g. https://shop.example}
        {--store-name=} {--store-email=}
        {--locale=en : Default language (en or bg)}
        {--currency=EUR : Default currency (ISO code)}
        {--country= : Store country (ISO code), used for tax before an address is known}
        {--timezone=UTC}
        {--net-prices : Catalog prices exclude tax (default: prices include tax)}
        {--admin-name=} {--admin-email=} {--admin-password=}
        {--generate-password : Generate the administrator password and print it once}
        {--demo : Add demo categories and products}';

    protected $description = 'Install PN Shop: check the server, configure the database, create the tables, store settings and the first administrator';

    public function handle(Requirements $requirements, Installation $installation, EnvironmentFile $env): int
    {
        if ($installation->isInstalled()) {
            $this->error('PN Shop is already installed. Use `php artisan pnshop:update` after updating the code.');

            return self::FAILURE;
        }

        if (! $this->checkRequirements($requirements)) {
            return self::FAILURE;
        }

        $values = $this->environmentValues();

        if ($values !== []) {
            // Configuration was loaded from the old .env (by packages too): continue in a new process.
            $env->set($values);
            $this->info('Saved the database settings to .env.');

            return $this->continueInNewProcess();
        }

        if (! $this->configureDatabase()) {
            return self::FAILURE;
        }

        $generated = $this->option('generate-password') ? Str::password(20) : null;

        $data = [
            'store_name' => $this->option('store-name') ?? text('Store name', default: (string) config('app.name'), required: true),
            'store_email' => $this->option('store-email') ?? ($this->input->isInteractive() ? text('Store contact email (optional)') : null),
            'locale' => $this->option('locale'),
            'currency' => $this->option('currency'),
            'country' => $this->option('country') ?? ($this->input->isInteractive() ? text('Store country code, e.g. BG (optional)') : null),
            'timezone' => $this->option('timezone'),
            'prices_include_tax' => ! $this->option('net-prices'),
            'admin_name' => $this->option('admin-name') ?? text('Administrator name', required: true),
            'admin_email' => $this->option('admin-email') ?? text('Administrator email', required: true),
            'admin_password' => $generated ?? $this->option('admin-password') ?? password('Administrator password', required: true),
            'demo' => (bool) $this->option('demo'),
        ];

        $validator = Validator::make($data, InstallOptions::rules());

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        try {
            $admin = app(Installer::class)->install(InstallOptions::fromArray($validator->validated()), fn (string $step) => $this->line("  · {$step}"));
        } catch (Throwable $e) {
            $this->error('Installation failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('PN Shop is installed.');
        $this->line('Admin panel: '.rtrim((string) config('app.url'), '/').'/admin'.' (sign in as '.$admin->email.')');

        if ($generated !== null) {
            $this->line("Generated password (shown once): {$generated}");
        }

        return self::SUCCESS;
    }

    private function checkRequirements(Requirements $requirements): bool
    {
        $checks = $requirements->check($this->option('db-connection') ?: null);

        foreach ($checks as $check) {
            if (! $check['ok']) {
                ($check['required'] ? $this->error(...) : $this->warn(...))(($check['required'] ? '✗ ' : '! ').$check['label'].' — '.$check['detail']);
            }
        }

        if (! Requirements::passes($checks)) {
            $this->error('Fix the requirements above and run the installer again.');

            return false;
        }

        $this->info('Server requirements are met.');

        return true;
    }

    /**
     * Write the database settings to .env (when given) and check the connection.
     */
    /**
     * @return array<string, string>
     */
    private function environmentValues(): array
    {
        return array_filter([
            'DB_CONNECTION' => (string) $this->option('db-connection'),
            'DB_HOST' => (string) $this->option('db-host'),
            'DB_PORT' => (string) $this->option('db-port'),
            'DB_DATABASE' => (string) $this->option('db-database'),
            'DB_USERNAME' => (string) $this->option('db-username'),
            'DB_PASSWORD' => (string) $this->option('db-password'),
            'APP_URL' => (string) $this->option('app-url'),
        ], fn (string $value) => $value !== '');
    }

    /**
     * Run the rest of the installation as `pnshop:install` again, with the other options.
     */
    private function continueInNewProcess(): int
    {
        $command = [PHP_BINARY, base_path('artisan'), 'pnshop:install'];

        foreach (['store-name', 'store-email', 'locale', 'currency', 'country', 'timezone', 'admin-name', 'admin-email', 'admin-password'] as $option) {
            if (($value = $this->option($option)) !== null && $value !== '') {
                $command[] = "--{$option}={$value}";
            }
        }

        foreach (['net-prices', 'generate-password', 'demo'] as $flag) {
            if ($this->option($flag)) {
                $command[] = "--{$flag}";
            }
        }

        if (! $this->input->isInteractive()) {
            $command[] = '--no-interaction';
        }

        // The keys of .env were exported into this process's environment, and an inherited
        // variable beats the file: drop them so the new process reads the updated .env.
        $inherited = array_fill_keys(array_keys(Dotenv::parse((string) @file_get_contents(app()->environmentFilePath()))), false);

        $process = new Process($command, base_path(), $inherited, timeout: null);

        if ($this->input->isInteractive() && Process::isTtySupported()) {
            $process->setTty(true);
        }

        return $process->run(fn (string $type, string $output) => $this->output->write($output));
    }

    private function configureDatabase(): bool
    {
        if (blank(config('app.key'))) {
            $this->call('key:generate', ['--force' => true]);
        }

        if (config('database.default') === 'sqlite') {
            $file = (string) config('database.connections.sqlite.database');

            if ($file !== ':memory:' && ! is_file($file)) {
                @touch($file);
            }
        }

        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $this->error('Cannot connect to the database: '.$e->getMessage());

            return false;
        }

        $this->info('Connected to the '.config('database.default').' database.');

        return true;
    }
}
