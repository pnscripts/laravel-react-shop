<?php

namespace PnShop\Catalog\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use PnShop\Catalog\Models\Product;
use PnShop\Catalog\ProductType;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['category', 'variants.stockLevels']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.title')
                    ->label('Category'),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (ProductType $state) => $state === ProductType::Variable ? 'Variants' : 'Simple'),
                TextColumn::make('variants_sku')
                    ->label('SKU')
                    ->state(fn (Product $record) => $record->sku)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('display_price')
                    ->label('Price')
                    ->state(fn (Product $record) => $record->lowestPrice()?->formatToLocale(app()->getLocale()))
                    ->description(fn (Product $record) => $record->hasVaryingPrices() ? 'from' : null)
                    ->placeholder('—'),
                TextColumn::make('available_stock')
                    ->label('Stock')
                    ->state(fn (Product $record) => $record->stock ?? '∞')
                    ->color(fn (mixed $state) => $state === 0 ? 'danger' : null),
                IconColumn::make('is_active')
                    ->label('Visible')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Visible'),
                SelectFilter::make('product_category_id')
                    ->label('Category')
                    ->relationship('category', 'title'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
