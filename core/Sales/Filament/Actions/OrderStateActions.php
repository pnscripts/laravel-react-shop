<?php

namespace PnShop\Sales\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use PnShop\Sales\Exceptions\OrderException;
use PnShop\Sales\Models\Order;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\FulfillmentStatus;
use PnShop\Sales\States\OrderState;
use PnShop\Sales\States\OrderStatus;
use PnShop\Sales\States\PaymentStatus;

/**
 * Admin actions that move an order through its state machines, or add a note.
 * Only the transitions allowed from the current state are offered.
 */
class OrderStateActions
{
    /**
     * @return list<Action>
     */
    public static function all(): array
    {
        return [
            self::make(OrderStatus::class, 'changeStatus', Heroicon::OutlinedArrowPath),
            self::make(PaymentStatus::class, 'changePayment', Heroicon::OutlinedBanknotes),
            self::make(FulfillmentStatus::class, 'changeFulfillment', Heroicon::OutlinedTruck),
            self::addNote(),
        ];
    }

    /**
     * @param  class-string<OrderStatus|PaymentStatus|FulfillmentStatus>  $state
     */
    public static function make(string $state, string $name, Heroicon $icon): Action
    {
        $current = fn (Order $record): OrderState => $record->{$state::field()};

        return Action::make($name)
            ->label('Update '.strtolower($state::fieldLabel()))
            ->icon($icon)
            ->authorize(fn (Order $record) => auth('admin')->user()?->can('update', $record) ?? false)
            ->visible(fn (Order $record) => $current($record)->transitions() !== [])
            ->schema([
                Select::make('state')
                    ->label($state::fieldLabel())
                    ->options(fn (Order $record) => collect($current($record)->transitions())
                        ->mapWithKeys(fn (OrderState $to) => [$to->value => $to->label()])
                        ->all())
                    ->required(),
                Textarea::make('note')->rows(2)->maxLength(1000),
            ])
            ->action(function (Order $record, array $data, Action $action) use ($state): void {
                try {
                    app(OrderWorkflow::class)->transition($record, $state::from($data['state']), auth('admin')->user(), $data['note'] ?: null);
                } catch (OrderException $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();
                    $action->halt();
                }

                $record->refresh();

                Notification::make()->success()->title($state::fieldLabel().' updated.')->send();
            });
    }

    public static function addNote(): Action
    {
        return Action::make('addNote')
            ->label('Add note')
            ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
            ->authorize(fn (Order $record) => auth('admin')->user()?->can('update', $record) ?? false)
            ->schema([Textarea::make('note')->required()->rows(3)->maxLength(1000)])
            ->action(function (Order $record, array $data): void {
                app(OrderWorkflow::class)->addNote($record, $data['note'], auth('admin')->user());

                Notification::make()->success()->title('Note added.')->send();
            });
    }
}
