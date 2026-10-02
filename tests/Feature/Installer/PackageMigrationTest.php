<?php

namespace Tests\Feature\Installer;

use Illuminate\Support\Facades\File;
use PnShop\Foundation\PnShop;
use PnShop\Installer\PackageMigration;
use RuntimeException;
use Tests\TestCase;

class PackageMigrationTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/pnshop-migration-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->root);

        // A 1.0 shop that unpacked the 1.1 archive over its files: the old core and storefront are still there.
        File::put($this->root.'/composer.json', (string) json_encode([
            'name' => 'acme/shop',
            'require' => ['php' => '^8.4', 'laravel/framework' => '^13.0', PnShop::PACKAGE => '^1.1@dev'],
            'repositories' => [['type' => 'path', 'url' => 'packages/pn-shop-core', 'options' => ['symlink' => true]], ['type' => 'composer', 'url' => 'https://repo.acme.test']],
            'autoload' => ['psr-4' => ['App\\' => 'app/', 'PnShop\\' => 'core/', 'Database\\Seeders\\' => 'database/seeders/']],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        foreach (['core/Acl/AclServiceProvider.php' => 'original', 'app/Http/Controllers/ShopController.php' => 'edited by the merchant', 'app/Http/Controllers/Acme/MyController.php' => 'the merchant\'s own'] as $file => $content) {
            File::ensureDirectoryExists(dirname($this->root.'/'.$file));
            File::put($this->root.'/'.$file, $content);
        }

        File::put($this->root.'/shipped.json', (string) json_encode([
            'core/Acl/AclServiceProvider.php' => hash('sha256', 'original'),
            'app/Http/Controllers/ShopController.php' => hash('sha256', 'as shipped'),
            'resources/js/app.tsx' => hash('sha256', 'not present'),
        ]));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    private function migration(): PackageMigration
    {
        return new PackageMigration($this->root, $this->root.'/shipped.json');
    }

    public function test_it_plans_the_switch_and_finds_the_files_left_from_1_0(): void
    {
        $plan = $this->migration()->plan();

        $this->assertSame([
            'require '.PnShop::PACKAGE.' ^1.1',
            'remove the path repository packages/pn-shop-core',
            'stop autoloading PnShop\\ from core/',
            'stop autoloading Database\\Seeders\\ from database/seeders/',
        ], $plan['composer']);
        $this->assertEquals(['app/Http/Controllers/ShopController.php' => true, 'core/Acl/AclServiceProvider.php' => false], $plan['leftovers']);
        $this->assertFalse($plan['local_package']);
        $this->assertSame([], $plan['problems']);
    }

    public function test_it_moves_the_files_aside_and_rewrites_composer_json(): void
    {
        $target = $this->migration()->run('2026-10-02-120000');

        $this->assertSame($this->root.'/storage/app/pnshop-migration/2026-10-02-120000', $target);
        $this->assertFileExists($target.'/core/Acl/AclServiceProvider.php');
        $this->assertFileExists($target.'/app/Http/Controllers/ShopController.php');
        $this->assertFileExists($target.'/composer.json');
        $this->assertDirectoryDoesNotExist($this->root.'/core');
        $this->assertFileExists($this->root.'/app/Http/Controllers/Acme/MyController.php', 'Files 1.0 did not ship stay.');

        $composer = json_decode((string) File::get($this->root.'/composer.json'), true);
        $this->assertSame('^1.1', $composer['require'][PnShop::PACKAGE]);
        $this->assertSame([['type' => 'composer', 'url' => 'https://repo.acme.test']], $composer['repositories']);
        $this->assertSame(['App\\' => 'app/'], $composer['autoload']['psr-4']);

        // Running it again changes nothing.
        $plan = $this->migration()->plan();
        $this->assertSame([], $plan['composer']);
        $this->assertSame([], $plan['leftovers']);
    }

    public function test_the_development_repository_is_refused(): void
    {
        $composer = json_decode((string) File::get($this->root.'/composer.json'), true);
        $composer['extra']['pnshop']['monorepo'] = true;
        File::put($this->root.'/composer.json', (string) json_encode($composer));

        $this->expectException(RuntimeException::class);
        $this->migration()->plan();
    }

    public function test_the_shipped_list_covers_the_1_0_core_and_storefront(): void
    {
        $shipped = json_decode((string) File::get(PnShop::path('resources/upgrade/1.0-files.json')), true);

        $this->assertArrayHasKey('core/Foundation/PnShop.php', $shipped);
        $this->assertArrayHasKey('app/Http/Controllers/CheckoutController.php', $shipped);
        $this->assertArrayHasKey('resources/js/app.tsx', $shipped);
        $this->assertArrayNotHasKey('app/Models/User.php', $shipped, 'Files the 1.1 project still has are not moved.');
        $this->assertArrayNotHasKey('routes/web.php', $shipped);
    }

    public function test_the_command_refuses_in_the_development_repository(): void
    {
        $this->artisan('pnshop:migrate-to-package', ['--dry-run' => true])
            ->expectsOutputToContain('development repository')
            ->assertFailed();
    }
}
