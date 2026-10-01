<?php

namespace Tests\Feature\Extensions;

use Illuminate\Support\Facades\File;
use PnShop\Extension\ExtensionManager;
use Tests\Feature\Admin\AdminTestCase;

/**
 * Works on a temporary copy of tests/Fixtures/extensions with its own boot cache.
 */
abstract class ExtensionTestCase extends AdminTestCase
{
    protected string $extensions;

    protected function setUp(): void
    {
        parent::setUp();

        $root = sys_get_temp_dir().'/pnshop-extensions-'.bin2hex(random_bytes(4));
        File::copyDirectory(base_path('tests/Fixtures/extensions'), $root.'/extensions');

        $this->extensions = $root.'/extensions';
        config([
            'pnshop.extensions.path' => $this->extensions,
            'pnshop.extensions.cache' => $root.'/plugins.php',
            'pnshop.extensions.trusted_keys' => [],
            'pnshop.extensions.require_signatures' => false,
            'pnshop.extensions.safe_mode' => false,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(dirname($this->extensions));

        parent::tearDown();
    }

    protected function manager(): ExtensionManager
    {
        return app(ExtensionManager::class);
    }
}
