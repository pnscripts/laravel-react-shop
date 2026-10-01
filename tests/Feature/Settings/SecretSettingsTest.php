<?php

namespace Tests\Feature\Settings;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PnShop\Acl\Models\AdminUser;
use PnShop\Settings\Filament\Pages\ManageSettings;
use PnShop\Settings\SettingDefinition;
use PnShop\Settings\Settings;
use PnShop\Settings\SettingsRegistry;
use PnShop\Settings\SettingsSchema;
use PnShop\Settings\SettingType;
use Tests\TestCase;

class SecretSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(SettingsRegistry::class)->register(new SettingsSchema('demo', 'Demo', new SettingDefinition('api_key', SettingType::Secret, 'API key')));
    }

    public function test_secrets_are_encrypted_at_rest_and_kept_when_left_empty(): void
    {
        $settings = app(Settings::class);
        $settings->set('demo', ['api_key' => 'sk_test_123']);

        $stored = (string) json_decode((string) DB::table('settings')->where('namespace', 'demo')->value('value'));
        $this->assertStringNotContainsString('sk_test_123', $stored);
        $this->assertSame('sk_test_123', $settings->get('demo.api_key'));
        $this->assertTrue($settings->hasSecret('demo.api_key'));

        $settings->set('demo', ['api_key' => '']);
        $this->assertSame('sk_test_123', $settings->get('demo.api_key'));

        $this->assertNull($settings->namespace('demo')['api_key']);
    }

    public function test_the_settings_page_never_shows_a_saved_secret(): void
    {
        Filament::setCurrentPanel('admin');
        app(Settings::class)->set('demo', ['api_key' => 'sk_test_hidden']);
        $this->actingAs(AdminUser::factory()->administrator()->create(), 'admin');

        Livewire::test(ManageSettings::class)
            ->assertDontSee('sk_test_hidden')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('sk_test_hidden', app(Settings::class)->get('demo.api_key'));
    }
}
