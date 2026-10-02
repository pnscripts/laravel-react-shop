<?php

namespace PnShop\Promotion\Filament\Resources\Promotions;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PnShop\Promotion\Contracts\ActionType;
use PnShop\Promotion\Contracts\ConditionType;
use PnShop\Promotion\Filament\Resources\Promotions\Pages\CreatePromotion;
use PnShop\Promotion\Filament\Resources\Promotions\Pages\EditPromotion;
use PnShop\Promotion\Filament\Resources\Promotions\Pages\ListPromotions;
use PnShop\Promotion\Filament\Resources\Promotions\RelationManagers\CouponsRelationManager;
use PnShop\Promotion\Models\Promotion;
use PnShop\Promotion\PromotionRegistry;
use UnitEnum;

class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        $registry = app(PromotionRegistry::class);

        return $schema->columns(3)->components([
            Section::make('Promotion')->columnSpan(2)->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('label')->label('Shown to customers as')->placeholder('The name')->maxLength(255),
                Textarea::make('description')->label('Internal note')->rows(2)->columnSpanFull(),
            ]),
            Section::make('Availability')->columnSpan(1)->schema([
                Toggle::make('is_active')->label('Active')->default(true),
                Toggle::make('requires_coupon')->label('Needs a coupon code')->helperText('Otherwise it applies automatically to every cart that meets the conditions.'),
                DateTimePicker::make('starts_at')->label('Starts'),
                DateTimePicker::make('ends_at')->label('Ends')->after('starts_at'),
            ]),
            Section::make('Conditions')
                ->description('All of them must hold. With none, the promotion applies to every cart.')
                ->columnSpan(2)
                ->schema([
                    Builder::make('conditions')
                        ->hiddenLabel()
                        ->blocks(array_values(array_map(
                            fn (ConditionType $type) => Block::make($type->key())->label($type->label())->schema($type->fields()),
                            $registry->conditions(),
                        )))
                        ->addActionLabel('Add condition')
                        ->blockNumbers(false)
                        ->collapsible()
                        ->default([]),
                ]),
            Section::make('Order and limits')->columnSpan(1)->schema([
                TextInput::make('position')->label('Order')->integer()->default(0)->helperText('Lower runs first.'),
                Toggle::make('stop_further')->label('Stop later promotions when this applies'),
                TextInput::make('usage_limit')->label('Total uses')->integer()->minValue(1)->placeholder('Unlimited'),
                TextInput::make('usage_limit_per_customer')->label('Uses per customer')->integer()->minValue(1)->placeholder('Unlimited'),
            ]),
            Section::make('Discounts')
                ->columnSpan(2)
                ->schema([
                    Builder::make('actions')
                        ->hiddenLabel()
                        ->blocks(array_values(array_map(
                            fn (ActionType $type) => Block::make($type->key())->label($type->label())->schema($type->fields()),
                            $registry->actions(),
                        )))
                        ->addActionLabel('Add discount')
                        ->blockNumbers(false)
                        ->collapsible()
                        ->minItems(1)
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')->searchable()->description(fn (Promotion $record) => $record->requires_coupon ? 'Coupon' : 'Automatic'),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('starts_at')->label('Starts')->dateTime()->placeholder('—'),
                TextColumn::make('ends_at')->label('Ends')->dateTime()->placeholder('—'),
                TextColumn::make('times_used')->label('Used')->formatStateUsing(fn (int $state, Promotion $record) => $record->usage_limit ? "{$state} / {$record->usage_limit}" : (string) $state),
                TextColumn::make('position')->label('Order')->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [CouponsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPromotions::route('/'),
            'create' => CreatePromotion::route('/create'),
            'edit' => EditPromotion::route('/{record}/edit'),
        ];
    }
}
