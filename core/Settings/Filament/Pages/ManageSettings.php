<?php

namespace PnShop\Settings\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use PnShop\Settings\SettingDefinition;
use PnShop\Settings\Settings;
use PnShop\Settings\SettingsRegistry;
use PnShop\Settings\SettingType;
use UnitEnum;

/**
 * One form for every registered settings schema (core, themes and plugins), one tab each.
 *
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'Settings';

    protected static ?string $slug = 'settings';

    /** @var array<string, array<string, mixed>> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('system.settings.manage') ?? false;
    }

    public function mount(): void
    {
        $settings = app(Settings::class);
        $state = [];

        foreach (app(SettingsRegistry::class)->all() as $namespace => $schema) {
            $state[self::stateKey($namespace)] = $settings->namespace($namespace);
        }

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        $tabs = [];

        foreach (app(SettingsRegistry::class)->all() as $namespace => $settingsSchema) {
            $tabs[] = Tab::make($settingsSchema->label)
                ->statePath(self::stateKey($namespace))
                ->schema(array_values(array_map(
                    fn (SettingDefinition $definition) => self::field($definition),
                    $settingsSchema->definitions(),
                )));
        }

        return $schema
            ->statePath('data')
            ->components([Tabs::make('settings')->tabs($tabs)->persistTabInQueryString()]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save settings')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $settings = app(Settings::class);

        foreach (app(SettingsRegistry::class)->all() as $namespace => $schema) {
            $values = $state[self::stateKey($namespace)] ?? [];

            if ($values !== []) {
                $settings->set($namespace, $values);
            }
        }

        activity('settings')->log('Settings updated');

        Notification::make()->success()->title('Settings saved.')->send();
    }

    private static function stateKey(string $namespace): string
    {
        return str_replace('.', '__', $namespace);
    }

    private static function field(SettingDefinition $definition): Field
    {
        $field = match ($definition->type) {
            SettingType::Text => Textarea::make($definition->key)->rows(3),
            SettingType::Boolean => Toggle::make($definition->key),
            SettingType::Select => Select::make($definition->key)->options($definition->options),
            SettingType::Email => TextInput::make($definition->key)->email(),
            SettingType::Url => TextInput::make($definition->key)->url(),
            SettingType::Integer => TextInput::make($definition->key)->integer(),
            SettingType::Decimal => TextInput::make($definition->key)->numeric(),
            SettingType::String => TextInput::make($definition->key),
        };

        return $field
            ->label($definition->label)
            ->helperText($definition->help)
            ->required($definition->required)
            ->rules($definition->rules);
    }
}
