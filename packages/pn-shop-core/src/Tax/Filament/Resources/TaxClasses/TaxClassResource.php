<?php

namespace PnShop\Tax\Filament\Resources\TaxClasses;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PnShop\Tax\Filament\Resources\TaxClasses\Pages\ManageTaxClasses;
use PnShop\Tax\Models\TaxClass;
use UnitEnum;

class TaxClassResource extends Resource
{
    protected static ?string $model = TaxClass::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Store';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(100)->helperText('e.g. Standard, Reduced, Zero rate.'),
            Toggle::make('is_default')->label('Default class')->helperText('Used by products and shipping methods without a class.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                IconColumn::make('is_default')->label('Default')->boolean(),
                TextColumn::make('rates_count')->counts('rates')->label('Rates'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTaxClasses::route('/')];
    }
}
