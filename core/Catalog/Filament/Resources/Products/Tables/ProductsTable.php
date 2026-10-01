<?php

namespace PnShop\Catalog\Filament\Resources\Products\Tables;

use Brick\Money\Money;
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

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.title')
                    ->label('Category'),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('price')
                    ->formatStateUsing(fn (?Money $state) => $state?->formatToLocale(app()->getLocale()))
                    ->sortable(),
                TextColumn::make('discount_price')
                    ->formatStateUsing(fn (?Money $state) => $state?->formatToLocale(app()->getLocale()))
                    ->placeholder('—'),
                TextColumn::make('stock')
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state) => $state === 0 ? 'danger' : null),
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
