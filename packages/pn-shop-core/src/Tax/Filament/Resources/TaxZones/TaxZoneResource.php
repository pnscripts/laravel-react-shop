<?php

namespace PnShop\Tax\Filament\Resources\TaxZones;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PnShop\Localization\Models\Country;
use PnShop\Tax\Filament\Resources\TaxZones\Pages\CreateTaxZone;
use PnShop\Tax\Filament\Resources\TaxZones\Pages\EditTaxZone;
use PnShop\Tax\Filament\Resources\TaxZones\Pages\ListTaxZones;
use PnShop\Tax\Filament\Resources\TaxZones\RelationManagers\RatesRelationManager;
use PnShop\Tax\Models\TaxZone;
use UnitEnum;

class TaxZoneResource extends Resource
{
    protected static ?string $model = TaxZone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Store';

    protected static ?int $navigationSort = 41;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('position')->integer()->default(0)->helperText('Zones are checked from the lowest number; the first match is used.'),
                Select::make('countries')
                    ->multiple()
                    ->searchable()
                    ->options(fn () => Country::query()->where('is_active', true)->get()->mapWithKeys(fn (Country $country) => [$country->code => $country->name()])->sort()->all())
                    ->helperText('Leave empty to match every country.')
                    ->columnSpanFull(),
                TagsInput::make('postcodes')->placeholder('1000, 4*, …')->helperText('Optional; * matches anything.')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('countries')->badge()->placeholder('All countries')->limitList(6),
                TextColumn::make('rates_count')->counts('rates')->label('Rates'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [RatesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxZones::route('/'),
            'create' => CreateTaxZone::route('/create'),
            'edit' => EditTaxZone::route('/{record}/edit'),
        ];
    }
}
