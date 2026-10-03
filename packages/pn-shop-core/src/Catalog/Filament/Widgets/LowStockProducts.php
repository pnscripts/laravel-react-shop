<?php

namespace PnShop\Catalog\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use PnShop\Catalog\Filament\Resources\Products\ProductResource;
use PnShop\Catalog\Models\ProductVariant;

/**
 * Tracked, active variants with few units left (on hand minus reserved): at or below the
 * variant's own low-stock threshold, or THRESHOLD when it has none.
 */
class LowStockProducts extends TableWidget
{
    public const THRESHOLD = 5;

    private const AVAILABLE_SQL = '(select coalesce(sum(on_hand - reserved), 0) from stock_levels where stock_levels.product_variant_id = product_variants.id)';

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
            ->query(ProductVariant::query()
                ->select('product_variants.*')
                ->selectRaw(self::AVAILABLE_SQL.' as available_units')
                ->with(['product', 'optionValues'])
                ->where('track_inventory', true)
                ->where('is_active', true)
                ->whereHas('product', fn (Builder $product) => $product->where('is_active', true))
                ->whereRaw(self::AVAILABLE_SQL.' <= coalesce(product_variants.low_stock_threshold, ?)', [self::THRESHOLD])
                ->orderByRaw(self::AVAILABLE_SQL))
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('product.title')->label('Product')
                    ->description(fn (ProductVariant $record) => $record->label() ?: null),
                TextColumn::make('sku')->label('SKU')->placeholder('—'),
                TextColumn::make('available_units')->label('Available')
                    ->color(fn (mixed $state) => (int) $state === 0 ? 'danger' : 'warning'),
            ])
            ->recordActions([
                Action::make('edit')->url(fn (ProductVariant $record) => ProductResource::getUrl('edit', ['record' => $record->product_id])),
            ]);
    }
}
