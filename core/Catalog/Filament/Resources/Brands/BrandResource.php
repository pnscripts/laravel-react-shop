<?php

namespace PnShop\Catalog\Filament\Resources\Brands;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PnShop\Catalog\Filament\Resources\Brands\Pages\CreateBrand;
use PnShop\Catalog\Filament\Resources\Brands\Pages\EditBrand;
use PnShop\Catalog\Filament\Resources\Brands\Pages\ListBrands;
use PnShop\Catalog\Models\Brand;
use PnShop\Localization\Filament\TranslationsSection;
use UnitEnum;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')->maxLength(255)->helperText('Generated from the name when empty.'),
                Toggle::make('is_active')->label('Visible in the store')->default(true),
                Textarea::make('description')->rows(4)->columnSpanFull(),
            ]),
            TranslationsSection::make([
                'name' => fn (string $name) => TextInput::make($name)->label('Name')->maxLength(255),
                'slug' => fn (string $name) => TextInput::make($name)->label('URL slug')->maxLength(255),
                'description' => fn (string $name) => Textarea::make($name)->label('Description')->rows(3),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->color('gray'),
                TextColumn::make('products_count')->counts('products')->label('Products')->sortable(),
                IconColumn::make('is_active')->label('Visible')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBrands::route('/'),
            'create' => CreateBrand::route('/create'),
            'edit' => EditBrand::route('/{record}/edit'),
        ];
    }
}
