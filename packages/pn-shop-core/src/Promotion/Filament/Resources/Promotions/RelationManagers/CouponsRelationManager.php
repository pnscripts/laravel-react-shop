<?php

namespace PnShop\Promotion\Filament\Resources\Promotions\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use PnShop\Promotion\CouponCodes;
use PnShop\Promotion\Models\Coupon;
use PnShop\Promotion\Models\Promotion;

class CouponsRelationManager extends RelationManager
{
    protected static string $relationship = 'coupons';

    protected static bool $isLazy = false;

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('code')
                ->required()
                ->maxLength(64)
                ->regex('/^[A-Za-z0-9_-]+$/')
                ->helperText('Letters, digits, - and _. Customers can type it in any case.')
                ->dehydrateStateUsing(fn (string $state) => Coupon::normalize($state))
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, ?string $state) => $rule->where('code', Coupon::normalize((string) $state))),
            TextInput::make('usage_limit')->label('Uses')->integer()->minValue(1)->placeholder('Unlimited (the promotion\'s limits still apply)'),
            Toggle::make('is_active')->label('Active')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->fontFamily('mono')->copyable()->searchable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('times_used')->label('Used')->formatStateUsing(fn (int $state, Coupon $record) => $record->usage_limit ? "{$state} / {$record->usage_limit}" : (string) $state),
            ])
            ->headerActions([
                CreateAction::make(),
                Action::make('generate')
                    ->label('Generate codes')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->schema([
                        TextInput::make('count')->label('How many')->integer()->minValue(1)->maxValue(1000)->default(10)->required(),
                        TextInput::make('prefix')->maxLength(20)->regex('/^[A-Za-z0-9_-]*$/'),
                        TextInput::make('usage_limit')->label('Uses per code')->integer()->minValue(1)->default(1),
                    ])
                    ->action(function (array $data): void {
                        /** @var Promotion $promotion */
                        $promotion = $this->getOwnerRecord();
                        $codes = app(CouponCodes::class)->generate($promotion, (int) $data['count'], (string) ($data['prefix'] ?? ''), isset($data['usage_limit']) ? (int) $data['usage_limit'] : null);

                        Notification::make()->success()->title(count($codes).' codes created.')->send();
                    }),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
