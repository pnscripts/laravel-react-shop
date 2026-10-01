<?php

namespace PnShop\Customer\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PnShop\Sales\Filament\Resources\Orders\OrderResource;
use PnShop\Sales\Models\Order;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static bool $isLazy = false;

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['items']))
            ->columns([
                TextColumn::make('number')->label('Order'),
                TextColumn::make('status')->badge(),
                TextColumn::make('payment_status')->label('Payment')->badge(),
                TextColumn::make('fulfillment_status')->label('Fulfillment')->badge(),
                TextColumn::make('total')->state(fn (Order $record) => $record->grandTotal()->formatToLocale(app()->getLocale())),
                TextColumn::make('created_at')->label('Placed')->dateTime(),
            ])
            ->recordActions([
                Action::make('view')->url(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
