<?php

namespace Tests\Feature\Installer;

use Dotenv\Dotenv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PnShop\Acl\Models\AdminUser;
use PnShop\Foundation\PnShop;
use PnShop\Installer\Backup\LocalBackup;
use PnShop\Installer\EnvironmentFile;
use PnShop\Installer\Installation;
use PnShop\Installer\Updater;
use PnShop\Localization\Localization;
use PnShop\Settings\Settings;
use PnShop\Theme\ThemeManager;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    use RefreshDatabase;

    private function installation(): Installation
    {
        $installation = app(Installation::class);
        $installation->forget();

        return $installation;
    }

    public function test_the_command_installs_the_shop_without_default_accounts(): void
    {
        $this->assertFalse($this->installation()->isInstalled());

        $this->artisan('pnshop:install', [
            '--store-name' => 'Acme Shop',
            '--store-email' => 'hello@acme.test',
            '--locale' => 'bg',
            '--currency' => 'EUR',
            '--country' => 'bg',
            '--timezone' => 'Europe/Sofia',
            '--admin-name' => 'Ana Admin',
            '--admin-email' => 'Ana@Acme.test',
            '--generate-password' => true,
            '--no-interaction' => true,
        ])->expectsOutputToContain('Generated password (shown once)')->assertSuccessful();

        $admin = AdminUser::query()->sole();
        $this->assertSame('ana@acme.test', $admin->email);
        $this->assertTrue($admin->isAdministrator());

        $settings = app(Settings::class);
        $settings->flush();
        $this->assertSame('Acme Shop', $settings->get('store.name'));
        $this->assertSame('Europe/Sofia', $settings->get('localization.timezone'));
        $this->assertSame('BG', $settings->get('tax.store_country'));
        $this->assertTrue($settings->get('tax.prices_include_tax'));

        $localization = app(Localization::class);
        $localization->flush();
        $this->assertSame('bg', $localization->defaultLocale());
        $this->assertSame('EUR', $localization->defaultCurrency()->code);

        $this->assertTrue(DB::table('payment_methods')->exists());
        $this->assertSame('install', DB::table('system_versions')->value('action'));
        $this->assertFileExists(Installation::lockPath());

        $this->artisan('pnshop:install', ['--no-interaction' => true])->expectsOutputToContain('already installed')->assertFailed();
    }

    public function test_invalid_answers_are_refused(): void
    {
        $this->artisan('pnshop:install', [
            '--store-name' => 'Acme',
            '--currency' => 'EURO',
            '--admin-name' => 'A',
            '--admin-email' => 'not-an-email',
            '--admin-password' => 'short',
            '--no-interaction' => true,
        ])->assertFailed();

        $this->assertSame(0, AdminUser::query()->count());
        $this->assertFalse($this->installation()->isInstalled());
    }

    public function test_a_shop_with_staff_accounts_counts_as_installed(): void
    {
        AdminUser::factory()->create();

        $this->assertTrue($this->installation()->isInstalled());
        $this->assertFileExists(Installation::lockPath());
    }

    public function test_every_page_leads_to_the_installer_until_installed(): void
    {
        config(['pnshop.installer.enforce' => true]);

        $this->get('/')->assertRedirect(url('/install'));
        $this->getJson('/api/store/v1/store')->assertStatus(503);
        $this->get('/up')->assertOk();

        $this->get('/install')->assertOk()->assertSee('PHP extension intl')->assertSee('Continue');
        $this->get('/install/store')->assertOk()->assertSee('Administrator');

        $this->post('/install/database', ['connection' => 'mysql', 'host' => '127.0.0.1', 'port' => 1, 'database' => 'x', 'username' => 'x'])
            ->assertSessionHasErrors('connection');

        $this->post('/install/store', [
            'store_name' => 'Web Shop',
            'locale' => 'en',
            'currency' => 'USD',
            'timezone' => 'UTC',
            'prices_include_tax' => '1',
            'admin_name' => 'Owner',
            'admin_email' => 'owner@example.test',
            'admin_password' => 'a-Long-password-1',
            'admin_password_confirmation' => 'a-Long-password-1',
        ])->assertOk()->assertSee('PN Shop is installed');

        $this->assertTrue(AdminUser::query()->where('email', 'owner@example.test')->sole()->isAdministrator());

        // Installed: the installer is gone and the shop opens.
        $this->get('/install')->assertNotFound();
        $this->get('/install/store')->assertNotFound();
        $this->get('/')->assertOk();
    }

    public function test_the_installer_requires_a_confirmed_password(): void
    {
        $this->post('/install/store', [
            'store_name' => 'Web Shop', 'locale' => 'en', 'currency' => 'USD', 'timezone' => 'UTC',
            'admin_name' => 'Owner', 'admin_email' => 'owner@example.test',
            'admin_password' => 'a-Long-password-1', 'admin_password_confirmation' => 'different',
        ])->assertSessionHasErrors('admin_password');

        $this->assertSame(0, AdminUser::query()->count());
    }

    public function test_update_dry_run_and_run(): void
    {
        AdminUser::factory()->administrator()->create();
        DB::table('system_versions')->insert(['version' => '0.9.0', 'action' => 'install', 'created_at' => now()]);

        $this->artisan('pnshop:update', ['--dry-run' => true])
            ->expectsOutputToContain('Database version: 0.9.0')
            ->expectsOutputToContain('Dry run: nothing was changed.')
            ->assertSuccessful();

        $this->assertSame(1, DB::table('system_versions')->count());

        $this->artisan('pnshop:update', ['--no-backup' => true, '--force' => true])
            ->expectsOutputToContain('PN Shop is up to date')
            ->assertSuccessful();

        $latest = DB::table('system_versions')->orderByDesc('id')->first();
        $this->assertSame('update', $latest->action);
        $this->assertSame('0.9.0', $latest->from_version);
        $this->assertSame(PnShop::VERSION, $latest->version);
        $this->assertFalse(app()->isDownForMaintenance());
    }

    public function test_update_refuses_older_code_and_a_core_that_differs_from_composer_lock(): void
    {
        AdminUser::factory()->administrator()->create();
        DB::table('system_versions')->insert(['version' => '9.0.0', 'action' => 'install', 'created_at' => now()]);

        $this->artisan('pnshop:update', ['--dry-run' => true])
            ->expectsOutputToContain('newer than this code')
            ->assertFailed();

        $updater = app(Updater::class);
        $this->assertSame([], $updater->codeProblems(PnShop::VERSION));

        $lock = tempnam(sys_get_temp_dir(), 'lock');
        File::put($lock, (string) json_encode(['packages' => [['name' => PnShop::PACKAGE, 'version' => '9.9.9', 'dist' => ['reference' => 'abc']]]]));
        $this->assertStringContainsString('Run `composer install` first', implode(' ', $updater->codeProblems(PnShop::VERSION, $lock)));

        // The repository's own lock matches vendor/.
        $this->assertSame([], $updater->codeProblems(PnShop::VERSION, base_path('composer.lock')));
        File::delete($lock);
    }

    public function test_update_warns_when_the_active_theme_does_not_support_the_new_version(): void
    {
        AdminUser::factory()->administrator()->create();
        DB::table('system_versions')->insert(['version' => PnShop::VERSION, 'action' => 'install', 'created_at' => now()]);

        $root = sys_get_temp_dir().'/pnshop-themes-'.bin2hex(random_bytes(4));
        File::copyDirectory(base_path('tests/Fixtures/themes/acme/child'), "{$root}/acme/child");
        $manifest = json_decode((string) File::get("{$root}/acme/child/pnshop.json"), true);
        File::put("{$root}/acme/child/pnshop.json", (string) json_encode([...$manifest, 'requires' => ['pnshop' => '^9.0']]));
        config(['pnshop.themes.path' => $root]);
        app()->forgetInstance(ThemeManager::class);
        app(Settings::class)->set('appearance', ['theme' => 'acme/child']);

        try {
            $this->artisan('pnshop:update', ['--dry-run' => true])
                ->expectsOutputToContain('uses the default theme until this is fixed')
                ->expectsOutputToContain('acme/child: Needs PN Shop ^9.0')
                ->assertSuccessful();
        } finally {
            File::deleteDirectory($root);
        }
    }

    public function test_update_needs_an_installed_shop(): void
    {
        $this->artisan('pnshop:update', ['--dry-run' => true])->assertFailed();
    }

    public function test_local_backup_copies_an_sqlite_database_and_the_env(): void
    {
        $directory = sys_get_temp_dir().'/pnshop-backup-test-'.getmypid();
        File::ensureDirectoryExists($directory);
        $database = $directory.'/shop.sqlite';
        file_put_contents($database, 'sqlite-bytes');
        $this->app->useStoragePath($directory.'/storage');

        $default = config('database.default');
        config(['database.default' => 'backup_source', 'database.connections.backup_source' => ['driver' => 'sqlite', 'database' => $database]]);

        try {
            $location = app(LocalBackup::class)->backup('test');
        } finally {
            config(['database.default' => $default]);
        }

        $this->assertStringStartsWith($directory.'/storage/app/backups/', $location);
        $this->assertSame('sqlite-bytes', file_get_contents($location.'/database.sqlite'));
        $this->assertSame(0700, fileperms($location) & 0777);

        File::deleteDirectory($directory);
    }

    public function test_environment_file_edits_keep_other_lines_and_quote_values(): void
    {
        $path = sys_get_temp_dir().'/pnshop-env-test-'.getmypid();
        file_put_contents($path, "APP_NAME=Laravel\n# DB_HOST=127.0.0.1\nDB_CONNECTION=sqlite\n");

        $env = new EnvironmentFile($path);
        $env->set(['DB_CONNECTION' => 'mysql', 'DB_HOST' => 'db.local', 'DB_PASSWORD' => 'p"a$s word', 'NEW_KEY' => true]);

        $this->assertSame("APP_NAME=Laravel\nDB_HOST=db.local\nDB_CONNECTION=mysql\nDB_PASSWORD=\"p\\\"a\\\$s word\"\nNEW_KEY=true\n", file_get_contents($path));
        $this->assertSame('mysql', $env->get('DB_CONNECTION'));

        $parsed = Dotenv::parse((string) file_get_contents($path));
        $this->assertSame('p"a$s word', $parsed['DB_PASSWORD']);

        unlink($path);
    }

    public function test_the_admin_sees_the_version_history(): void
    {
        $admin = AdminUser::factory()->administrator()->create();
        DB::table('system_versions')->insert(['version' => PnShop::VERSION, 'action' => 'install', 'created_at' => now()]);

        $this->actingAs($admin, 'admin')->get('/admin/system/updates')->assertOk()->assertSee(PnShop::VERSION)->assertSee('PHP extension intl');
    }

    public function test_an_unreachable_database_never_opens_the_installer(): void
    {
        $default = config('database.default');
        config([
            'pnshop.installer.enforce' => true,
            'database.connections.unreachable' => ['driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 1, 'database' => 'x', 'username' => 'x', 'password' => ''],
            'database.default' => 'unreachable',
        ]);

        $this->assertSame(Installation::UNKNOWN, $this->installation()->state());

        try {
            $this->get('/install')->assertStatus(503)->assertSee('cannot reach its database');
            $this->get('/')->assertStatus(503);
        } finally {
            // The test transaction is rolled back on the default connection.
            config(['database.default' => $default]);
        }
    }
}
