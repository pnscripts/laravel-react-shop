<?php

namespace PnShop\Localization\Filament\Resources\Languages;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PnShop\Localization\Filament\Resources\Languages\Pages\CreateLanguage;
use PnShop\Localization\Filament\Resources\Languages\Pages\EditLanguage;
use PnShop\Localization\Filament\Resources\Languages\Pages\ListLanguages;
use PnShop\Localization\Models\Language;
use UnitEnum;

class LanguageResource extends Resource
{
    protected static ?string $model = Language::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static string|UnitEnum|null $navigationGroup = 'Store';

    protected static ?int $navigationSort = 60;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('code')
                    ->required()
                    ->maxLength(12)
                    ->regex('/^[a-z]{2,3}(-[A-Z]{2})?$/')
                    ->helperText('Language code used in URLs, e.g. de or pt-BR.')
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?Language $record) => $record !== null),
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('native_name')->label('Name in the language itself')->required()->maxLength(255),
                TextInput::make('sort_order')->integer()->default(0),
                Toggle::make('is_active')
                    ->label('Available in the store')
                    ->default(true)
                    ->disabled(fn (?Language $record) => $record?->is_default === true)
                    ->helperText(fn (?Language $record) => $record?->is_default ? 'The default language is the store\'s source language and is always available.' : null),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('code')->badge(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('native_name'),
                IconColumn::make('is_default')->label('Default')->boolean(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLanguages::route('/'),
            'create' => CreateLanguage::route('/create'),
            'edit' => EditLanguage::route('/{record}/edit'),
        ];
    }
}
