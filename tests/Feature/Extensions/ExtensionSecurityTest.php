<?php

namespace Tests\Feature\Extensions;

use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PnShop\Extension\Exceptions\ExtensionException;
use PnShop\Extension\ExtensionStatus;
use PnShop\Extension\Filament\Pages\ManageExtensions;
use PnShop\Extension\Models\Extension;
use PnShop\Extension\PackageIntegrity;
use PnShop\Extension\ZipPackage;
use ZipArchive;

class ExtensionSecurityTest extends ExtensionTestCase
{
    public function test_signatures_from_trusted_keys_are_required_when_configured(): void
    {
        config(['pnshop.extensions.require_signatures' => true]);

        $this->assertStringContainsString('not signed', implode(' ', $this->manager()->problems($this->manager()->find('acme/good'))));

        $pair = sodium_crypto_sign_keypair();
        PackageIntegrity::sign($this->extensions.'/acme/good', 'acme-2026', base64_encode(sodium_crypto_sign_secretkey($pair)));

        // Unknown key.
        $this->assertStringContainsString('unknown key', implode(' ', $this->manager()->problems($this->manager()->find('acme/good'))));

        config(['pnshop.extensions.trusted_keys' => ['acme-2026' => base64_encode(sodium_crypto_sign_publickey($pair))]]);
        $this->assertSame([], $this->manager()->problems($this->manager()->find('acme/good')));

        // Any change to the files breaks the signature.
        File::append($this->extensions.'/acme/good/src/GoodPlugin.php', "\n// changed\n");
        $this->expectExceptionMessage('signature does not match');
        $this->manager()->install('acme/good');
    }

    public function test_a_plugin_archive_is_extracted_after_checking_its_manifest(): void
    {
        $zip = $this->zip(['good/pnshop.json' => File::get($this->extensions.'/acme/good/pnshop.json'), 'good/src/GoodPlugin.php' => File::get($this->extensions.'/acme/good/src/GoodPlugin.php')]);
        File::deleteDirectory($this->extensions.'/acme/good');

        $manifest = ZipPackage::extract($zip, $this->extensions);

        $this->assertSame('acme/good', $manifest->id);
        $this->assertFileExists($this->extensions.'/acme/good/src/GoodPlugin.php');
        $this->assertSame([], glob($this->extensions.'/.staging-*'));

        $this->expectExceptionMessage('already present');
        ZipPackage::extract($zip, $this->extensions);
    }

    /**
     * @return array<string, array{array<string, string>, string}>
     */
    public static function unsafeArchives(): array
    {
        $manifest = '{"id":"evil/zip","name":"Evil","version":"1.0.0","provider":"Evil\\\\Zip\\\\P","autoload":{"psr-4":{"Evil\\\\Zip\\\\":"src/"}}}';

        return [
            'path traversal' => [['pnshop.json' => $manifest, '../../escape.php' => '<?php'], 'unsafe path'],
            'absolute path' => [['pnshop.json' => $manifest, '/etc/cron.d/x.php' => '<?php'], 'unsafe path'],
            'executable' => [['pnshop.json' => $manifest, 'bin/run.sh' => 'rm -rf /'], 'not allowed'],
            'phar' => [['pnshop.json' => $manifest, 'src/x.phar' => 'x'], 'not allowed'],
            'no manifest' => [['src/P.php' => '<?php'], 'no pnshop.json'],
            'invalid manifest' => [['pnshop.json' => '{"id":"BAD"}'], 'manifest'],
        ];
    }

    #[DataProvider('unsafeArchives')]
    public function test_unsafe_archives_are_refused_before_writing_anything(array $files, string $error): void
    {
        $before = File::allFiles($this->extensions);

        try {
            ZipPackage::extract($this->zip($files), $this->extensions);
            $this->fail('The archive should be refused.');
        } catch (ExtensionException $e) {
            $this->assertStringContainsStringIgnoringCase($error, $e->getMessage());
        }

        $this->assertCount(count($before), File::allFiles($this->extensions));
        $this->assertDirectoryDoesNotExist($this->extensions.'/evil');
    }

    public function test_symbolic_links_in_archives_are_refused(): void
    {
        $path = sys_get_temp_dir().'/pnshop-link-'.bin2hex(random_bytes(4)).'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('pnshop.json', File::get($this->extensions.'/acme/good/pnshop.json'));
        $zip->addFromString('src/link.php', '/etc/passwd');
        $zip->setExternalAttributesName('src/link.php', ZipArchive::OPSYS_UNIX, (0120777 << 16));
        $zip->close();

        $this->expectExceptionMessage('symbolic link');
        ZipPackage::extract($path, $this->extensions);
    }

    public function test_the_cli_manages_plugins(): void
    {
        $this->artisan('pnshop:plugin:list')->assertSuccessful()->expectsOutputToContain('acme/good');
        $this->artisan('pnshop:plugin', ['action' => 'install', 'id' => 'acme/good'])->assertSuccessful();
        $this->artisan('pnshop:plugin', ['action' => 'enable', 'id' => 'acme/good'])->assertSuccessful();
        $this->artisan('pnshop:plugin', ['action' => 'verify', 'id' => 'acme/good'])->assertSuccessful();
        $this->artisan('pnshop:plugin', ['action' => 'uninstall', 'id' => 'acme/good'])->assertFailed()->expectsOutputToContain('Disable acme/good');
        $this->artisan('pnshop:plugin', ['action' => 'install', 'id' => 'acme/nope'])->assertFailed();
    }

    public function test_extensions_are_managed_in_the_admin(): void
    {
        $this->actingAsAdministrator();

        Livewire::test(ManageExtensions::class)
            ->assertSee('Good plugin')
            ->assertSee('Needs the plugin acme/good ^2.0')
            ->callTableAction('install', 'acme/good')
            ->callTableAction('enable', 'acme/good');

        $this->assertSame(ExtensionStatus::Enabled, Extension::query()->find('acme/good')->status);

        Livewire::test(ManageExtensions::class)->assertActionHidden('upload');
    }

    public function test_only_staff_with_the_permission_reach_extensions(): void
    {
        $this->actingAsStaff(['system.settings.manage']);

        $this->get(ManageExtensions::getUrl())->assertForbidden();
    }

    /**
     * @param  array<string, string>  $files
     */
    private function zip(array $files): string
    {
        $path = sys_get_temp_dir().'/pnshop-zip-'.bin2hex(random_bytes(4)).'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        return $path;
    }
}
