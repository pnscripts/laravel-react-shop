<?php

namespace PnShop\Localization\Filament\Resources\Countries;

use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use PnShop\Localization\Filament\Resources\Countries\Pages\ListCountries;
use PnShop\Localization\Models\Country;
use UnitEnum;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Store';

    protected static ?int $navigationSort = 80;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')->badge()->searchable(),
                TextColumn::make('name')->state(fn (Country $record) => $record->name()),
                ToggleColumn::make('is_active')->label('Active'),
            ])
            ->filters([TernaryFilter::make('is_active')->label('Active')])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')->action(fn (Collection $records) => Country::query()->whereKey($records->modelKeys())->update(['is_active' => true])),
                    BulkAction::make('deactivate')->action(fn (Collection $records) => Country::query()->whereKey($records->modelKeys())->update(['is_active' => false])),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCountries::route('/')];
    }
}
