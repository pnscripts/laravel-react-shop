<?php

namespace PnShop\Catalog\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use PnShop\Catalog\Filament\Resources\Categories\CategoryResource;
use PnShop\Catalog\Models\Product;
use PnShop\Localization\Filament\TranslationsSection;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Product')
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Select::make('product_category_id')
                            ->label('Primary category')
                            ->helperText('Used for breadcrumbs and the product\'s main URL.')
                            ->options(fn () => CategoryResource::parentOptions(null))
                            ->searchable()
                            ->required(),
                        Select::make('categories')
                            ->label('Also show in')
                            ->multiple()
                            ->relationship('categories', 'title')
                            ->options(fn () => CategoryResource::parentOptions(null))
                            ->searchable()
                            // The primary category always stays among the product's categories.
                            ->saveRelationshipsUsing(fn (Product $record, ?array $state) => $record->categories()->sync(
                                array_values(array_unique([...array_map('intval', $state ?? []), (int) $record->product_category_id])),
                            )),
                        Select::make('brand_id')
                            ->label('Brand')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload(),
                        Textarea::make('description')
                            ->rows(6),
                        TextInput::make('image')
                            ->label('Image URL')
                            ->url()
                            ->maxLength(2048),
                    ]),
                Section::make('Pricing and stock')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Visible in the store')
                            ->default(true),
                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('discount_price')
                            ->numeric()
                            ->minValue(0)
                            ->lt('price')
                            ->helperText('Leave empty for no discount.'),
                        TextInput::make('stock')
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(255),
                        TextInput::make('barcode')
                            ->maxLength(255),
                    ]),
                TranslationsSection::make([
                    'title' => fn (string $name) => TextInput::make($name)->label('Title')->maxLength(255),
                    'slug' => fn (string $name) => TextInput::make($name)->label('URL slug')->maxLength(255)
                        ->helperText('Generated from the title when empty.'),
                    'description' => fn (string $name) => Textarea::make($name)->label('Description')->rows(6),
                ])->columnSpanFull(),
            ]);
    }
}
