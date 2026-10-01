<?php

namespace PnShop\Shipping\Filament\Resources\ShippingMethods;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use PnShop\Localization\Filament\TranslationsSection;
use PnShop\Settings\Filament\SettingField;
use PnShop\Settings\SettingDefinition;
use PnShop\Shipping\Filament\Resources\ShippingMethods\Pages\CreateShippingMethod;
use PnShop\Shipping\Filament\Resources\ShippingMethods\Pages\EditShippingMethod;
use PnShop\Shipping\Filament\Resources\ShippingMethods\Pages\ListShippingMethods;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\ShippingCarrierManager;
use UnitEnum;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Store';

    protected static ?int $navigationSort = 31;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        $carriers = app(ShippingCarrierManager::class);
        $definitions = fn (?string $carrier): array => $carrier !== null && $carriers->has($carrier) ? $carriers->get($carrier)->settings() : [];

        return $schema->columns(3)->components([
            Section::make()->columnSpan(2)->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255)->helperText('Shown to customers at checkout.'),
                Select::make('shipping_zone_id')->label('Zone')->relationship('zone', 'name')->required()->preload(),
                Select::make('carrier')
                    ->options($carriers->options())
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('settings', collect($definitions($state))
                        ->mapWithKeys(fn (SettingDefinition $definition) => [$definition->key => $definition->default])
                        ->all()))
                    ->helperText('How the price is calculated.'),
                Textarea::make('description')->rows(2)->helperText('Optional, e.g. delivery times.')->columnSpanFull(),
            ]),
            Section::make('Availability')->columnSpan(1)->schema([
                Toggle::make('is_active')->label('Offered at checkout')->default(true),
                TextInput::make('position')->integer()->default(0),
            ]),
            Section::make('Price')
                ->columnSpan(2)
                ->statePath('settings')
                ->visible(fn (Get $get) => $definitions($get('carrier')) !== [])
                ->schema(fn (Get $get) => array_map(fn (SettingDefinition $definition) => SettingField::make($definition), $definitions($get('carrier')))),
            TranslationsSection::make([
                'name' => fn (string $name) => TextInput::make($name)->label('Name')->maxLength(255),
                'description' => fn (string $name) => Textarea::make($name)->label('Description')->rows(2),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $carriers = app(ShippingCarrierManager::class);

        return $table
            ->defaultSort('position')
            ->modifyQueryUsing(fn ($query) => $query->with('zone'))
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('zone.name')->label('Zone'),
                TextColumn::make('carrier')
                    ->formatStateUsing(fn (string $state) => $carriers->has($state) ? $carriers->get($state)->label() : "{$state} (not installed)")
                    ->color(fn (string $state) => $carriers->has($state) ? null : 'danger'),
                IconColumn::make('is_active')->label('Offered')->boolean(),
            ])
            ->filters([SelectFilter::make('shipping_zone_id')->label('Zone')->relationship('zone', 'name')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShippingMethods::route('/'),
            'create' => CreateShippingMethod::route('/create'),
            'edit' => EditShippingMethod::route('/{record}/edit'),
        ];
    }
}
