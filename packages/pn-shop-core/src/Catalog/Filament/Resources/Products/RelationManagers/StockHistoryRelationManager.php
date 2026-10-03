<?php

namespace PnShop\Catalog\Filament\Resources\Products\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use PnShop\Inventory\Models\StockMovement;
use PnShop\Inventory\StockMovementReason;

/**
 * The product's stock ledger (all its variants): every reservation, shipment, return and
 * adjustment, with who made it. Read-only: stock changes through the variant fields.
 */
class StockHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    protected static ?string $title = 'Stock history';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth('admin')->user()?->can('catalog.products.view') ?? false;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['variant.optionValues', 'adminUser']))
            ->defaultSort('stock_movements.id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime(),
                TextColumn::make('variant')
                    ->label('Variant')
                    ->state(fn (StockMovement $record) => $record->variant?->label() ?: ($record->variant->sku ?? '—')),
                TextColumn::make('reason')
                    ->badge()
                    ->formatStateUsing(fn (StockMovementReason $state) => $state->label()),
                TextColumn::make('quantity')
                    ->label('Change')
                    ->formatStateUsing(fn (int $state) => $state > 0 ? "+{$state}" : (string) $state)
                    ->color(fn (int $state) => $state < 0 ? 'danger' : 'success'),
                TextColumn::make('on_hand_after')->label('On hand after'),
                TextColumn::make('adminUser.name')->label('By')->placeholder('—'),
                TextColumn::make('note')->placeholder('—')->limit(60),
            ])
            ->filters([
                SelectFilter::make('reason')->options(collect(StockMovementReason::cases())->mapWithKeys(fn (StockMovementReason $reason) => [$reason->value => $reason->label()])->all()),
            ]);
    }
}
