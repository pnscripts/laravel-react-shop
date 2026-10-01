<?php

namespace PnShop\Sales\Filament\Resources\Orders\RelationManagers;

use App\Models\User;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PnShop\Acl\Models\AdminUser;
use PnShop\Sales\Models\OrderHistory;
use PnShop\Sales\States\FulfillmentStatus;
use PnShop\Sales\States\OrderStatus;
use PnShop\Sales\States\PaymentStatus;

/**
 * The order's timeline: every state change and note, with who made it.
 */
class HistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'history';

    protected static ?string $title = 'History';

    protected static bool $isLazy = false;

    /** @var array<string, class-string<OrderStatus|PaymentStatus|FulfillmentStatus>> */
    private const STATES = [
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'fulfillment_status' => FulfillmentStatus::class,
    ];

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('actor')->reorder()->orderByDesc('id'))
            ->paginated([25, 50])
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime(),
                TextColumn::make('field')->label('What')
                    ->formatStateUsing(fn (string $state) => isset(self::STATES[$state]) ? self::STATES[$state]::fieldLabel() : 'Note'),
                TextColumn::make('change')->label('Change')
                    ->state(fn (OrderHistory $record) => self::describe($record))
                    ->placeholder('—'),
                TextColumn::make('note')->wrap()->placeholder('—'),
                TextColumn::make('actor')->label('By')
                    ->state(fn (OrderHistory $record) => match (true) {
                        $record->actor instanceof AdminUser => $record->actor->name.' (staff)',
                        $record->actor instanceof User => $record->actor->name.' (customer)',
                        default => 'System',
                    }),
            ]);
    }

    private static function describe(OrderHistory $record): ?string
    {
        $state = self::STATES[$record->field] ?? null;

        if ($state === null || $record->to === null) {
            return null;
        }

        $label = fn (?string $value): ?string => $value === null ? null : ($state::tryFrom($value)?->label() ?? $value);

        return $record->from === null ? $label($record->to) : $label($record->from).' → '.$label($record->to);
    }
}
