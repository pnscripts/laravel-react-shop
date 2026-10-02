<?php

namespace PnShop\Customer\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PnShop\Customer\Models\CustomerAddress;
use PnShop\Localization\Models\Country;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static bool $isLazy = false;

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('first_name')->required()->maxLength(100),
            TextInput::make('last_name')->required()->maxLength(100),
            TextInput::make('company')->maxLength(150)->columnSpanFull(),
            TextInput::make('line1')->label('Street address')->required()->maxLength(255)->columnSpanFull(),
            TextInput::make('line2')->label('Apartment, floor, etc.')->maxLength(255)->columnSpanFull(),
            TextInput::make('city')->required()->maxLength(100),
            TextInput::make('postcode')->maxLength(32),
            TextInput::make('region')->maxLength(100),
            Select::make('country_code')
                ->label('Country')
                ->options(fn () => Country::query()->where('is_active', true)->get()->mapWithKeys(fn (Country $country) => [$country->code => $country->name()])->sort())
                ->searchable()
                ->required(),
            TextInput::make('phone')->tel()->maxLength(50),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('address')->state(fn (CustomerAddress $record) => implode(', ', $record->toPostalAddress()->lines())),
                IconColumn::make('is_default_shipping')->label('Default shipping')->boolean(),
                IconColumn::make('is_default_billing')->label('Default billing')->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
