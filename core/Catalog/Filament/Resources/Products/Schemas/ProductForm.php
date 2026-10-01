<?php

namespace PnShop\Catalog\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                            ->label('Category')
                            ->relationship('category', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
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
            ]);
    }
}
