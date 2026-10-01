<?php

namespace PnShop\Sales\Filament\Resources\Orders\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use PnShop\Sales\Filament\Actions\ChangeOrderStatusAction;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('name')->label('Customer')->searchable()
                    ->description(fn ($record) => $record->email),
                TextColumn::make('email')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('orderStatus.name')->label('Status')->badge(),
                TextColumn::make('paymentMethod.name')->label('Payment'),
                TextColumn::make('created_at')->label('Placed')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('order_status_id')->label('Status')->relationship('orderStatus', 'name'),
                SelectFilter::make('payment_method_id')->label('Payment')->relationship('paymentMethod', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
                ChangeOrderStatusAction::make(),
            ]);
    }
}
