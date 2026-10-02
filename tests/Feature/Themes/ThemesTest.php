<?php

namespace Tests\Feature\Themes;

use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PnShop\Settings\SettingDefinition;
use PnShop\Settings\Settings;
use PnShop\Settings\SettingsRegistry;
use PnShop\Settings\SettingsSchema;
use PnShop\Settings\SettingType;
use PnShop\Storefront\Http\Middleware\HandleInertiaRequests;
use PnShop\Theme\Filament\Pages\ManageThemes;
use PnShop\Theme\ThemeManager;
use Tests\Feature\Admin\AdminTestCase;

class ThemesTest extends AdminTestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/pnshop-themes-'.bin2hex(random_bytes(4));
        File::copyDirectory(base_path('tests/Fixtures/themes'), $this->root.'/themes');
        File::copyDirectory(base_path('themes/pnshop/default'), $this->root.'/themes/pnshop/default');
        File::ensureDirectoryExists($this->root.'/public');

        config(['pnshop.themes.path' => $this->root.'/themes']);
        $this->app->usePublicPath($this->root.'/public');
        $this->app->forgetInstance(ThemeManager::class);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_themes_are_discovered_and_problems_reported(): void
    {
        $invalid = [];
        $themes = app(ThemeManager::class);

        $this->assertSame(['acme/child', 'acme/orphan', 'pnshop/default'], $themes->discover($invalid)->keys()->all());
        $this->assertStringContainsString('type must be "theme"', (string) reset($invalid));
        $this->assertStringContainsString('acme/missing is missing', implode(' ', $themes->problems($themes->find('acme/orphan'))));
        $this->assertSame([], $themes->problems($themes->find('acme/child')));
        $this->assertSame('pnshop/default', $themes->active()->id);
    }

    public function test_activating_a_theme_publishes_it_and_the_storefront_loads_its_bundle(): void
    {
        app(ThemeManager::class)->activate('acme/child');

        $this->assertFileExists($this->root.'/public/themes/acme/child/build/manifest.json');
        $this->app->forgetInstance(ThemeManager::class);
        $this->withVite();

        $html = (string) $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('/themes/acme/child/build/assets/app-child.js', $html);
        $this->assertStringContainsString('/themes/acme/child/build/assets/app-child.css', $html);
        $this->assertStringContainsString(':root{--radius:1rem}', $html);
        // A light brand colour gets dark text.
        $this->assertStringContainsString(':root:not(.dark){--primary:#ffd400;--primary-foreground:#111111}', $html);
        $this->assertStringNotContainsString('/build/assets/app-', str_replace('/themes/acme/child/build/assets/app-', '', $html));
    }

    public function test_theme_settings_become_css_variables_and_props(): void
    {
        app(ThemeManager::class)->activate('acme/child');
        $this->app->forgetInstance(ThemeManager::class);
        $this->refreshThemeSettings();

        app(Settings::class)->set('theme.acme_child', ['primary' => '#1e3a8a', 'radius' => '0rem']);

        $this->assertStringContainsString(':root:not(.dark){--primary:#1e3a8a;--primary-foreground:#ffffff}', app(ThemeManager::class)->css());
        $this->get('/')->assertInertia(fn ($page) => $page->where('theme.id', 'acme/child')->where('theme.settings.radius', '0rem'));

        // Unsafe values never reach the CSS.
        $this->expectException(ValidationException::class);
        app(Settings::class)->set('theme.acme_child', ['primary' => 'red;}body{display:none']);
    }

    public function test_a_missing_or_unbuilt_active_theme_falls_back_to_the_default(): void
    {
        app(ThemeManager::class)->activate('acme/child');
        File::deleteDirectory($this->root.'/themes/acme/child/dist');
        $this->app->forgetInstance(ThemeManager::class);

        $this->assertSame('pnshop/default', app(ThemeManager::class)->active()->id);
        $this->get('/')->assertOk();

        $this->expectExceptionMessage('not built');
        app(ThemeManager::class)->activate('acme/child');
    }

    public function test_switching_themes_changes_the_asset_version(): void
    {
        $before = $this->get('/')->headers->get('X-Inertia-Version') ?? $this->inertiaVersion();
        app(ThemeManager::class)->activate('acme/child');
        $this->app->forgetInstance(ThemeManager::class);

        $this->assertNotSame($before, $this->inertiaVersion());
    }

    public function test_staff_activate_themes_in_the_admin_and_the_cli(): void
    {
        $this->actingAsAdministrator();

        Livewire::test(ManageThemes::class)
            ->assertSee('Acme child')
            ->assertSee('acme/missing is missing')
            ->callTableAction('activate', 'acme/child');

        $this->assertSame('acme/child', app(Settings::class)->get('appearance.theme'));

        $this->artisan('pnshop:theme', ['action' => 'activate', 'id' => 'pnshop/default'])->assertSuccessful();
        $this->artisan('pnshop:theme', ['action' => 'activate', 'id' => 'acme/orphan'])->assertFailed();
        $this->artisan('pnshop:theme', ['action' => 'list'])->assertSuccessful()->expectsOutputToContain('acme/child');
    }

    public function test_contrast_colours(): void
    {
        $this->assertSame('#ffffff', ThemeManager::contrast('#000'));
        $this->assertSame('#111111', ThemeManager::contrast('#ffffff'));
        $this->assertSame('#ffffff', ThemeManager::contrast('#4f46e5'));
    }

    private function inertiaVersion(): string
    {
        return (string) app(HandleInertiaRequests::class)->version(request());
    }

    private function refreshThemeSettings(): void
    {
        $registry = app(SettingsRegistry::class);
        $theme = app(ThemeManager::class)->active();
        $registry->register(new SettingsSchema('theme.'.$theme->key(), 'Theme', ...array_map(
            fn (array $setting) => new SettingDefinition($setting['key'], SettingType::from($setting['type']), $setting['label'], default: $setting['default'] ?? null, options: $setting['options'] ?? []),
            app(ThemeManager::class)->settingsFor($theme),
        )));
    }
}
