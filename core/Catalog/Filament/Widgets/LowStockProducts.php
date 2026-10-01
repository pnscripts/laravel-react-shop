<?php

namespace PnShop\Catalog\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use PnShop\Catalog\Filament\Resources\Products\ProductResource;
use PnShop\Catalog\Models\Product;

class LowStockProducts extends TableWidget
{
    public const THRESHOLD = 5;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth('admin')->user()?->can('catalog.products.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Low stock')
            ->query(Product::query()->active()->where('stock', '<=', self::THRESHOLD)->orderBy('stock'))
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('sku')->label('SKU'),
                TextColumn::make('stock')->color(fn (int $state) => $state === 0 ? 'danger' : 'warning'),
            ])
            ->recordActions([
                Action::make('edit')->url(fn (Product $record) => ProductResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
