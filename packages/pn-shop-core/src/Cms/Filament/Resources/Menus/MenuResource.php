<?php

namespace PnShop\Cms\Filament\Resources\Menus;

use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PnShop\Cms\Filament\Resources\Menus\Pages\EditMenu;
use PnShop\Cms\Filament\Resources\Menus\Pages\ListMenus;
use PnShop\Cms\Filament\Resources\Menus\RelationManagers\ItemsRelationManager;
use PnShop\Cms\Models\Menu;
use UnitEnum;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(100),
            TextInput::make('code')->disabled()->helperText('Used by the theme to place the menu.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('code')->badge()->color('gray'),
                TextColumn::make('items_count')->counts('items')->label('Items'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function canCreate(): bool
    {
        // The theme decides which menus exist (header, footer).
        return false;
    }

    public static function getRelations(): array
    {
        return [ItemsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }
}
