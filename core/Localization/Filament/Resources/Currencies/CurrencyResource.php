<?php

namespace PnShop\Localization\Filament\Resources\Currencies;

use BackedEnum;
use Brick\Money\Currency as BrickCurrency;
use Brick\Money\Exception\UnknownCurrencyException;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PnShop\Localization\Filament\Resources\Currencies\Pages\CreateCurrency;
use PnShop\Localization\Filament\Resources\Currencies\Pages\EditCurrency;
use PnShop\Localization\Filament\Resources\Currencies\Pages\ListCurrencies;
use PnShop\Localization\Models\Currency;
use UnitEnum;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Store';

    protected static ?int $navigationSort = 70;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('code')
                    ->label('ISO code')
                    ->required()
                    ->length(3)
                    ->dehydrateStateUsing(fn (string $state) => strtoupper($state))
                    ->unique(ignoreRecord: true)
                    ->rule(fn () => function (string $attribute, mixed $value, Closure $fail): void {
                        try {
                            BrickCurrency::of(strtoupper((string) $value));
                        } catch (UnknownCurrencyException) {
                            $fail('This is not an ISO 4217 currency code.');
                        }
                    })
                    ->disabled(fn (?Currency $record) => $record !== null),
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('exchange_rate')
                    ->numeric()
                    ->minValue(0.00000001)
                    ->required()
                    ->default(1)
                    ->helperText('Units of this currency per one unit of the default currency.')
                    ->disabled(fn (?Currency $record) => $record?->is_default === true),
                Toggle::make('is_active')
                    ->default(true)
                    ->disabled(fn (?Currency $record) => $record?->is_default === true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->badge()->searchable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('exchange_rate')->numeric(4),
                IconColumn::make('is_default')->label('Default')->boolean(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCurrencies::route('/'),
            'create' => CreateCurrency::route('/create'),
            'edit' => EditCurrency::route('/{record}/edit'),
        ];
    }
}
