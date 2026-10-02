<?php

namespace PnShop\Installer\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use PnShop\Foundation\PnShop;
use PnShop\Installer\Installation;
use PnShop\Installer\Requirements;
use UnitEnum;

/**
 * Version, update history and server checks. Updates themselves run on the command line
 * (`php artisan pnshop:update`), where a long migration cannot time out.
 */
class SystemUpdates extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'Version and updates';

    protected static ?string $slug = 'system/updates';

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('system.settings.manage') ?? false;
    }

    public function content(Schema $schema): Schema
    {
        $installation = app(Installation::class);
        $database = $installation->installedVersion();

        return $schema->components([
            Section::make('Version')->schema([
                Text::make('Code: PN Shop '.PnShop::VERSION.'. Database: '.($database ?? 'unknown').'.'),
                Text::make($database !== null && $database !== PnShop::VERSION
                    ? 'The code is newer than the database: run `php artisan pnshop:update` to finish the update.'
                    : 'To update: back up, put the new code in place (composer update or a release archive), then run `php artisan pnshop:update`. See the update guide.')->color('gray'),
            ]),
            Section::make('History')->schema([EmbeddedTable::make()]),
            Section::make('Server')->schema(array_map(
                fn (array $check) => Text::make(($check['ok'] ? '✓ ' : ($check['required'] ? '✗ ' : '– ')).$check['label'].' — '.$check['detail'])
                    ->color($check['ok'] ? 'gray' : ($check['required'] ? 'danger' : 'warning')),
                app(Requirements::class)->check(),
            )),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => app(Installation::class)->history())
            ->columns([
                TextColumn::make('action')->badge(),
                TextColumn::make('version'),
                TextColumn::make('from_version')->label('From')->placeholder('—'),
                TextColumn::make('created_at')->label('When')->dateTime(),
            ])
            ->paginated(false);
    }
}
