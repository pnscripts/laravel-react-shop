<?php

namespace PnShop\Plugins\HandlingFee\Filament\Resources\Exemptions;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PnShop\Plugins\HandlingFee\Filament\Resources\Exemptions\Pages\ManageExemptions;
use PnShop\Plugins\HandlingFee\Models\Exemption;
use UnitEnum;

class ExemptionResource extends Resource
{
    protected static ?string $model = Exemption::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Extensions';

    protected static ?string $navigationLabel = 'Fee exemptions';

    protected static ?string $modelLabel = 'fee exemption';

    protected static ?string $slug = 'fee-exemptions';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_group_id')->label('Customer group')->relationship('customerGroup', 'name')->required()->unique(ignoreRecord: true),
            TextInput::make('note')->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customerGroup.name')->label('Customer group'),
                TextColumn::make('note')->placeholder('—'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageExemptions::route('/')];
    }
}
