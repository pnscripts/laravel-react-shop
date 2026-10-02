<?php

namespace PnShop\Payment\Filament\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PnShop\Payment\Models\Refund;
use PnShop\Payment\Models\RefundLine;

class RefundsRelationManager extends RelationManager
{
    protected static string $relationship = 'refunds';

    protected static ?string $title = 'Refunds';

    protected static bool $isLazy = false;

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('lines.item'))
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime(),
                TextColumn::make('amount')->state(fn (Refund $record) => $record->amount->formatToLocale(app()->getLocale())),
                TextColumn::make('status')->badge()->color(fn (string $state) => $state === Refund::COMPLETED ? 'success' : 'danger'),
                TextColumn::make('items')
                    ->state(fn (Refund $record) => $record->lines->map(fn (RefundLine $line) => $line->quantity.' × '.$line->item?->product_title)->all())
                    ->listWithLineBreaks()
                    ->placeholder('—'),
                IconColumn::make('restock')->boolean(),
                TextColumn::make('reason')->wrap()->placeholder('—'),
            ]);
    }
}
