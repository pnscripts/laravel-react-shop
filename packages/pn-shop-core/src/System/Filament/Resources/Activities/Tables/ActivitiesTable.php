<?php

namespace PnShop\System\Filament\Resources\Activities\Tables;

use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime()->sortable(),
                TextColumn::make('causer.name')->label('Who')->placeholder('System'),
                TextColumn::make('log_name')->label('Log')->badge(),
                TextColumn::make('description')->searchable(),
                TextColumn::make('subject_type')->label('Subject')
                    ->formatStateUsing(fn (?string $state, Activity $record) => $state ? class_basename($state).' #'.$record->subject_id : '—'),
            ])
            ->filters([
                SelectFilter::make('log_name')->label('Log')
                    ->options(fn () => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name', 'log_name')->filter()),
            ])
            ->recordActions([
                ViewAction::make()->schema([
                    KeyValueEntry::make('attribute_changes.attributes')->label('New values'),
                    KeyValueEntry::make('attribute_changes.old')->label('Old values'),
                ]),
            ]);
    }
}
