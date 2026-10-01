<?php

namespace PnShop\Tax\Filament\Resources\TaxZones\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RatesRelationManager extends RelationManager
{
    protected static string $relationship = 'rates';

    protected static ?string $title = 'Rates';

    protected static bool $isLazy = false;

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->required()->maxLength(100)->helperText('Shown on totals and invoices, e.g. "VAT 20%".'),
            Select::make('tax_class_id')->label('Tax class')->relationship('taxClass', 'name')->required()->preload(),
            TextInput::make('rate')->label('Rate (%)')->numeric()->minValue(0)->maxValue(100)->required(),
            TextInput::make('priority')->integer()->minValue(1)->default(1)->helperText('Rates of the same priority add up.'),
            Toggle::make('is_compound')->label('Compound')->helperText('Charged on the amount including the taxes of lower priorities.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('taxClass'))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('taxClass.name')->label('Class'),
                TextColumn::make('rate')->suffix('%'),
                TextColumn::make('priority'),
                IconColumn::make('is_compound')->label('Compound')->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
