<?php

namespace PnShop\Shipping\Filament\Resources\ShippingZones;

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
use PnShop\Shipping\Filament\Resources\ShippingZones\Pages\ManageShippingZones;
use PnShop\Shipping\Models\ShippingZone;
use UnitEnum;

class ShippingZoneResource extends Resource
{
    protected static ?string $model = ShippingZone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeEuropeAfrica;

    protected static string|UnitEnum|null $navigationGroup = 'Store';

    protected static ?int $navigationSort = 30;

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
                    ->helperText('Leave empty to match every country (a "rest of the world" zone).')
                    ->columnSpanFull(),
                TagsInput::make('postcodes')
                    ->placeholder('1000, 4*, …')
                    ->helperText('Optional. Limit the zone to these postcodes; * matches anything.')
                    ->columnSpanFull(),
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
                TextColumn::make('methods_count')->counts('methods')->label('Methods'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageShippingZones::route('/')];
    }
}
