<?php

namespace Tests\Feature\Extensions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PnShop\Extension\Exceptions\ExtensionException;
use PnShop\Extension\ExtensionManager;
use PnShop\Extension\ExtensionStatus;
use PnShop\Extension\Manifest;
use PnShop\Extension\Models\Extension;
use PnShop\Extension\PluginLoader;
use PnShop\Settings\Settings;
use Spatie\Permission\Models\Permission;

class ExtensionLifecycleTest extends ExtensionTestCase
{
    public function test_plugins_are_discovered_and_invalid_manifests_reported(): void
    {
        $invalid = [];
        $found = $this->manager()->discover($invalid);

        $this->assertSame(['acme/broken', 'acme/good', 'acme/needs-good'], $found->keys()->all());
        $this->assertCount(1, $invalid);
        $this->assertStringContainsString('id must look like', (string) reset($invalid));
    }

    public function test_install_enable_disable_and_uninstall(): void
    {
        $this->manager()->install('acme/good');

        $this->assertTrue(Schema::hasTable('acme_good_notes'));
        $this->assertSame(['installed'], DB::table('acme_good_notes')->pluck('note')->all());
        $this->assertSame(ExtensionStatus::Installed, Extension::query()->find('acme/good')->status);
        $this->assertSame([], PluginLoader::enabled());

        $this->manager()->enable('acme/good');

        // Booted at once: its route, setting and permission work.
        $this->assertArrayHasKey('acme/good', PluginLoader::enabled());
        $this->get('/acme-good')->assertOk()->assertSee('good plugin says Hello');
        $this->assertSame('Hello', app(Settings::class)->get('plugin.acme_good.greeting'));
        $this->assertTrue(Permission::query()->where('name', 'good.manage')->where('guard_name', 'admin')->exists());

        $this->manager()->disable('acme/good');
        $this->assertSame([], PluginLoader::enabled());

        // Uninstalling keeps the data by default.
        $this->manager()->uninstall('acme/good');
        $this->assertNull(Extension::query()->find('acme/good'));
        $this->assertTrue(Schema::hasTable('acme_good_notes'));
        $this->assertContains('uninstalled keeping data', DB::table('acme_good_notes')->pluck('note')->all());
    }

    public function test_uninstall_with_purge_removes_its_tables_and_settings(): void
    {
        $this->manager()->install('acme/good');
        $this->manager()->enable('acme/good');
        app(Settings::class)->set('plugin.acme_good', ['greeting' => 'Hi']);
        $this->manager()->disable('acme/good');

        $this->manager()->uninstall('acme/good', keepData: false);

        $this->assertFalse(Schema::hasTable('acme_good_notes'));
        $this->assertSame(0, DB::table('settings')->where('namespace', 'plugin.acme_good')->count());
        $this->assertSame(0, DB::table('extension_migrations')->count());
        $this->assertFalse(DB::table('migrations')->where('migration', 'like', '%acme_good%')->exists());

        // It can be installed again from scratch.
        $this->manager()->install('acme/good');
        $this->assertSame(['installed'], DB::table('acme_good_notes')->pluck('note')->all());
    }

    public function test_a_failed_install_rolls_back_its_migrations(): void
    {
        try {
            $this->manager()->install('acme/broken');
            $this->fail('The install should fail.');
        } catch (ExtensionException $e) {
            $this->assertStringContainsString('the second migration failed', $e->getMessage());
        }

        $this->assertFalse(Schema::hasTable('acme_broken_items'));
        $this->assertFalse(DB::table('migrations')->where('migration', 'like', '%acme_broken%')->exists());
        $extension = Extension::query()->find('acme/broken');
        $this->assertSame(ExtensionStatus::Failed, $extension->status);
        $this->assertStringContainsString('the second migration failed', (string) $extension->error);

        $this->expectException(ExtensionException::class);
        $this->manager()->enable('acme/broken');
    }

    public function test_requirements_are_checked_before_anything_runs(): void
    {
        $this->manager()->install('acme/good');
        $this->manager()->enable('acme/good');

        try {
            $this->manager()->install('acme/needs-good');
            $this->fail('A missing dependency version should block the install.');
        } catch (ExtensionException $e) {
            $this->assertStringContainsString('acme/good ^2.0', $e->getMessage());
        }

        $this->assertNull(Extension::query()->find('acme/needs-good'));

        File::put($this->extensions.'/acme/good/pnshop.json', str_replace('">=0.8"', '"^9.0"', File::get($this->extensions.'/acme/good/pnshop.json')));
        $this->assertStringContainsString('Needs PN Shop ^9.0', implode(' ', $this->manager()->problems($this->manager()->find('acme/good'))));
    }

    public function test_plugins_that_others_need_cannot_be_disabled(): void
    {
        File::put($this->extensions.'/acme/needs-good/pnshop.json', str_replace('"^2.0"', '"^1.0"', File::get($this->extensions.'/acme/needs-good/pnshop.json')));
        foreach (['acme/good', 'acme/needs-good'] as $id) {
            $this->manager()->install($id);
            $this->manager()->enable($id);
        }

        $this->expectExceptionMessage('Disable acme/needs-good first');
        $this->manager()->disable('acme/good');
    }

    public function test_updates_run_new_migrations_and_the_upgrade_hook(): void
    {
        $this->manager()->install('acme/good');
        $this->manager()->enable('acme/good');

        // Version 1.1.0 arrives with a new migration.
        File::move($this->extensions.'/acme/good/updates/2026_02_01_000000_add_level_to_acme_good_notes.php', $this->extensions.'/acme/good/database/migrations/2026_02_01_000000_add_level_to_acme_good_notes.php');
        File::put($this->extensions.'/acme/good/pnshop.json', str_replace('"1.0.0"', '"1.1.0"', File::get($this->extensions.'/acme/good/pnshop.json')));

        $this->manager()->update('acme/good');

        $this->assertTrue(Schema::hasColumn('acme_good_notes', 'level'));
        $this->assertContains('upgraded 1.0.0 to 1.1.0', DB::table('acme_good_notes')->pluck('note')->all());
        $this->assertSame('1.1.0', Extension::query()->find('acme/good')->version);
        $this->assertSame([], array_merge(...array_values($this->manager()->verify('acme/good'))));

        $this->expectExceptionMessage('already installed');
        $this->manager()->update('acme/good');
    }

    public function test_changed_files_are_detected(): void
    {
        $this->manager()->install('acme/good');

        File::append($this->extensions.'/acme/good/src/GoodPlugin.php', "\n// tampered\n");
        File::put($this->extensions.'/acme/good/src/Extra.php', '<?php');

        $changes = $this->manager()->verify('acme/good');

        $this->assertSame(['src/GoodPlugin.php'], $changes['changed']);
        $this->assertSame(['src/Extra.php'], $changes['added']);
    }

    public function test_safe_mode_boots_no_plugins(): void
    {
        $this->manager()->install('acme/good');
        $this->manager()->enable('acme/good');

        config(['pnshop.extensions.safe_mode' => true]);

        $this->assertSame([], PluginLoader::enabled());
    }

    public function test_every_action_is_audited(): void
    {
        $this->manager()->install('acme/good');
        $this->manager()->enable('acme/good');

        $this->assertSame(['installed', 'enabled'], DB::table('activity_log')->where('log_name', 'extensions')->orderBy('id')->pluck('event')->all());
    }

    public function test_storefront_scripts_must_be_built_files_inside_the_plugin(): void
    {
        $manifest = json_decode(File::get($this->extensions.'/acme/good/pnshop.json'), true);

        // A script at the plugin root is refused too: its folder (the whole plugin) would be published.
        File::put($this->extensions.'/acme/good/storefront.js', 'window.PnShop.registerSlot("footer.top", () => null);');

        foreach (['../../evil.js', '/etc/passwd.js', 'src/GoodPlugin.php', 'dist/missing.js', 'storefront.js'] as $script) {
            try {
                Manifest::fromArray([...$manifest, 'storefront' => $script], $this->extensions.'/acme/good');
                $this->fail("{$script} should be refused.");
            } catch (ExtensionException $e) {
                $this->assertStringContainsString('storefront must be', $e->getMessage());
            }
        }

        File::ensureDirectoryExists($this->extensions.'/acme/good/dist');
        File::put($this->extensions.'/acme/good/dist/storefront.js', 'window.PnShop.registerSlot("footer.top", () => null);');
        $this->assertSame('dist/storefront.js', Manifest::fromArray([...$manifest, 'storefront' => 'dist/storefront.js'], $this->extensions.'/acme/good')->storefront);
    }

    public function test_only_static_storefront_files_are_published(): void
    {
        $plugin = $this->extensions.'/acme/good';
        File::ensureDirectoryExists($plugin.'/dist');
        File::put($plugin.'/dist/storefront.js', 'export {};');
        File::put($plugin.'/dist/style.css', 'a{}');
        File::put($plugin.'/dist/shell.php', '<?php echo 1;');
        $manifest = json_decode(File::get($plugin.'/pnshop.json'), true);
        $public = sys_get_temp_dir().'/pnshop-public-'.getmypid();
        $this->app->usePublicPath($public);

        app(ExtensionManager::class)->publishStorefront(Manifest::fromArray([...$manifest, 'storefront' => 'dist/storefront.js'], $plugin));

        $this->assertFileExists($public.'/extensions/acme/good/storefront.js');
        $this->assertFileExists($public.'/extensions/acme/good/style.css');
        $this->assertFileDoesNotExist($public.'/extensions/acme/good/shell.php');

        File::deleteDirectory($public);
    }
}
