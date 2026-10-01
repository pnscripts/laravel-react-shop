<?php

namespace PnShop\Payment\Filament\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PnShop\Payment\Models\Payment;
use PnShop\Payment\Models\PaymentTransaction;

/**
 * An order's payments and, per payment, every exchange with the gateway.
 */
class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Payments';

    protected static bool $isLazy = false;

    public function isReadOnly(): bool
    {
        return true;
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            RepeatableEntry::make('transactions')
                ->columns(5)
                ->schema([
                    TextEntry::make('created_at')->label('When')->dateTime(),
                    TextEntry::make('type'),
                    TextEntry::make('outcome')->badge(),
                    TextEntry::make('amount')->state(fn (PaymentTransaction $record) => $record->amount?->formatToLocale(app()->getLocale()))->placeholder('—'),
                    TextEntry::make('message')->placeholder('—'),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('method'))
            ->columns([
                TextColumn::make('created_at')->label('Started')->dateTime(),
                TextColumn::make('method.name')->label('Method')->placeholder('—'),
                TextColumn::make('status')->badge(),
                TextColumn::make('amount')->state(fn (Payment $record) => $record->amount->formatToLocale(app()->getLocale())),
                TextColumn::make('refunded_amount')->label('Refunded')
                    ->state(fn (Payment $record) => $record->refunded_amount->isZero() ? null : $record->refunded_amount->formatToLocale(app()->getLocale()))
                    ->placeholder('—'),
                TextColumn::make('reference')->placeholder('—')->copyable(),
            ])
            ->recordActions([ViewAction::make()->label('Transactions')]);
    }
}
