<?php

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Livewire;
use PnShop\Acl\Models\AdminUser;
use PnShop\Settings\Filament\Pages\ManageSettings;
use PnShop\Settings\SettingDefinition;
use PnShop\Settings\Settings;
use PnShop\Settings\SettingsRegistry;
use PnShop\Settings\SettingsSchema;
use PnShop\Settings\SettingType;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private Settings $settings;

    protected function setUp(): void
    {
        parent::setUp();

        app(SettingsRegistry::class)->register(new SettingsSchema(
            'plugin.acme.demo',
            'Demo plugin',
            new SettingDefinition('enabled', SettingType::Boolean, 'Enabled', default: false),
            new SettingDefinition('limit', SettingType::Integer, 'Limit', default: 10, rules: ['min:1']),
            new SettingDefinition('mode', SettingType::Select, 'Mode', default: 'a', options: ['a' => 'A', 'b' => 'B']),
        ));

        $this->settings = app(Settings::class);
    }

    public function test_defaults_are_returned_until_a_value_is_stored(): void
    {
        $this->assertFalse($this->settings->get('plugin.acme.demo.enabled'));
        $this->assertSame(10, $this->settings->get('plugin.acme.demo.limit'));
        $this->assertSame(config('app.name'), $this->settings->get('store.name'));
    }

    public function test_values_are_validated_cast_and_cached(): void
    {
        $this->settings->set('plugin.acme.demo', ['enabled' => '1', 'limit' => '25', 'mode' => 'b']);

        $this->assertTrue($this->settings->get('plugin.acme.demo.enabled'));
        $this->assertSame(25, $this->settings->get('plugin.acme.demo.limit'));
        $this->assertSame('b', app(Settings::class)->get('plugin.acme.demo.mode'));
        $this->assertSame(['enabled' => true, 'limit' => 25, 'mode' => 'b'], $this->settings->namespace('plugin.acme.demo'));
    }

    public function test_invalid_values_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->settings->set('plugin.acme.demo', ['limit' => 0, 'mode' => 'z']);
    }

    public function test_unknown_settings_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->settings->get('plugin.acme.demo.missing');
    }

    public function test_namespaces_cannot_be_registered_twice(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(SettingsRegistry::class)->register(new SettingsSchema('store', 'Again'));
    }

    public function test_the_settings_page_saves_every_namespace(): void
    {
        $this->actingAs(AdminUser::factory()->administrator()->create(), 'admin');

        Livewire::test(ManageSettings::class)
            ->fillForm([
                'store' => ['name' => 'Corner Shop', 'email' => 'hello@example.com'],
                'plugin__acme__demo' => ['enabled' => true, 'limit' => 3, 'mode' => 'a'],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Corner Shop', app(Settings::class)->get('store.name'));
        $this->assertSame(3, app(Settings::class)->get('plugin.acme.demo.limit'));
        $this->assertDatabaseHas('activity_log', ['log_name' => 'settings']);
    }
}
