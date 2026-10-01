<?php

namespace PnShop\Installer\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PnShop\Installer\EnvironmentFile;
use PnShop\Installer\Installer;
use PnShop\Installer\InstallOptions;
use PnShop\Installer\Requirements;
use Throwable;

/**
 * The web installer for hosts without a command line: requirements, database, store and
 * administrator. It does the same as `php artisan pnshop:install`.
 */
class InstallerController
{
    public function requirements(Requirements $requirements): View
    {
        $checks = $requirements->check();

        return view('pnshop-installer::requirements', ['checks' => $checks, 'passes' => Requirements::passes($checks)]);
    }

    public function database(EnvironmentFile $env): View
    {
        $connection = (string) config('database.default');

        return view('pnshop-installer::database', [
            'configCached' => app()->configurationIsCached(),
            'values' => [
                'connection' => $connection,
                'host' => config("database.connections.{$connection}.host", '127.0.0.1'),
                'port' => config("database.connections.{$connection}.port", ''),
                'database' => $connection === 'sqlite' ? '' : config("database.connections.{$connection}.database", ''),
                'username' => config("database.connections.{$connection}.username", ''),
            ],
        ]);
    }

    /**
     * Check the connection with the entered settings, then write them to .env.
     */
    public function saveDatabase(Request $request, EnvironmentFile $env): RedirectResponse
    {
        $data = $request->validate([
            'connection' => ['required', Rule::in(['sqlite', 'mysql', 'mariadb', 'pgsql'])],
            'host' => ['required_unless:connection,sqlite', 'nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'database' => ['required_unless:connection,sqlite', 'nullable', 'string', 'max:255'],
            'username' => ['required_unless:connection,sqlite', 'nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);

        $connection = $data['connection'];
        $sqliteFile = database_path('database.sqlite');

        if ($connection === 'sqlite' && ! is_file($sqliteFile)) {
            @touch($sqliteFile);
        }

        // Try the entered settings on a connection of their own; the shop's connections stay as they are.
        Config::set('database.connections.pnshop_install_check', array_merge((array) config("database.connections.{$connection}"), $connection === 'sqlite'
            ? ['database' => $sqliteFile]
            : ['host' => $data['host'], 'port' => $data['port'] ?: config("database.connections.{$connection}.port"), 'database' => $data['database'], 'username' => $data['username'], 'password' => $data['password'] ?? '']));

        try {
            DB::connection('pnshop_install_check')->getPdo();
        } catch (Throwable) {
            // Generic on purpose: raw driver errors would let visitors map hosts and ports.
            throw ValidationException::withMessages(['connection' => 'Cannot connect to the database. Check the host, port, database name, user and password.']);
        } finally {
            DB::purge('pnshop_install_check');
        }

        $env->set($connection === 'sqlite'
            ? ['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $sqliteFile, 'APP_URL' => $request->root()]
            : [
                'DB_CONNECTION' => $connection,
                'DB_HOST' => $data['host'],
                'DB_PORT' => $data['port'] ?: null,
                'DB_DATABASE' => $data['database'],
                'DB_USERNAME' => $data['username'],
                'DB_PASSWORD' => $data['password'] ?? '',
                'APP_URL' => $request->root(),
            ]);

        return redirect()->to(url('/install/store'));
    }

    public function store(): View
    {
        return view('pnshop-installer::store', [
            'timezones' => timezone_identifiers_list(),
            'database' => (string) config('database.default'),
        ]);
    }

    /**
     * Run the installation. The finished page is rendered directly: after this request the
     * installer's pages are gone.
     */
    public function install(Request $request, Installer $installer): View
    {
        $data = Validator::make([
            ...$request->only(['store_name', 'store_email', 'locale', 'currency', 'country', 'timezone', 'admin_name', 'admin_email', 'admin_password', 'admin_password_confirmation']),
            'prices_include_tax' => $request->boolean('prices_include_tax'),
            'demo' => $request->boolean('demo'),
        ], [...InstallOptions::rules(), 'admin_password' => [...InstallOptions::rules()['admin_password'], 'confirmed']])->validate();

        try {
            $steps = [];
            $admin = $installer->install(InstallOptions::fromArray($data), function (string $step) use (&$steps) {
                $steps[] = $step;
            });
        } catch (Throwable $e) {
            throw ValidationException::withMessages(['install' => 'Installation failed: '.$e->getMessage()]);
        }

        return view('pnshop-installer::done', ['steps' => $steps, 'email' => $admin->email]);
    }
}
