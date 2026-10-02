<?php

namespace PnShop\Theme\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use PnShop\Extension\Exceptions\ExtensionException;
use PnShop\Settings\Filament\Pages\ManageSettings;
use PnShop\Theme\ThemeManager;
use PnShop\Theme\ThemeManifest;
use UnitEnum;

class ManageThemes extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static string|UnitEnum|null $navigationGroup = 'Appearance';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Themes';

    protected static ?string $slug = 'themes';

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('appearance.themes.manage') ?? false;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Text::make('The storefront theme decides how the shop looks. Themes run in visitors\' browsers; install only themes you trust.')->color('gray'),
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->rows())
            ->columns([
                TextColumn::make('name')->description(fn (array $record) => $record['description']),
                TextColumn::make('version')->description(fn (array $record) => $record['id']),
                TextColumn::make('parent')->label('Extends')->placeholder('—'),
                TextColumn::make('state')->badge()->color(fn (string $state) => match ($state) {
                    'Active' => 'success',
                    'Ready' => 'gray',
                    default => 'danger',
                })->wrap(),
            ])
            ->recordActions([
                Action::make('activate')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->visible(fn (array $record) => $record['state'] === 'Ready')
                    ->requiresConfirmation()
                    ->modalDescription('Visitors see the new theme on their next page load.')
                    ->action(fn (array $record) => $this->run(fn (ThemeManager $themes) => $themes->activate($record['id'], auth('admin')->user()), "Activated {$record['name']}.")),
                Action::make('customize')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->visible(fn (array $record) => $record['state'] === 'Active' && $record['configurable'])
                    ->url(fn (array $record) => ManageSettings::getUrl(['tab' => 'theme__'.$record['key']])),
                Action::make('publish')
                    ->label('Publish files again')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (array $record) => $record['state'] === 'Active' && ! $record['builtin'])
                    ->action(fn (array $record) => $this->run(fn (ThemeManager $themes) => $themes->publish($themes->find($record['id'])), 'Theme files published.')),
            ]);
    }

    private function run(\Closure $operation, string $success): void
    {
        try {
            $operation(app(ThemeManager::class));
        } catch (ExtensionException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return;
        }

        Notification::make()->success()->title($success)->send();
        $this->resetTable();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function rows(): array
    {
        $themes = app(ThemeManager::class);
        $active = $themes->active()->id;

        return $themes->discover()->map(function (ThemeManifest $theme) use ($themes, $active) {
            $problems = $themes->problems($theme);

            return [
                'id' => $theme->id,
                'key' => $theme->key(),
                'name' => $theme->name,
                'description' => $theme->description,
                'version' => $theme->version,
                'parent' => $theme->parent,
                'builtin' => $theme->builtin,
                'configurable' => $problems === [] && $themes->settingsFor($theme) !== [],
                'state' => $theme->id === $active ? 'Active' : ($problems === [] ? 'Ready' : implode(' ', $problems)),
            ];
        })->all();
    }
}
