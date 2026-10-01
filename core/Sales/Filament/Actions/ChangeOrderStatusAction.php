<?php

namespace PnShop\Sales\Filament\Actions;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Services\OrderStatusService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ChangeOrderStatusAction
{
    public static function make(): Action
    {
        return Action::make('changeStatus')
            ->label('Change status')
            ->icon(Heroicon::OutlinedArrowPath)
            ->authorize(fn (Order $record) => auth('admin')->user()?->can('update', $record) ?? false)
            ->fillForm(fn (Order $record) => ['order_status_id' => $record->order_status_id])
            ->schema([
                Select::make('order_status_id')
                    ->label('Status')
                    ->options(fn () => OrderStatus::query()->orderBy('name')->pluck('name', 'id'))
                    ->required(),
            ])
            ->action(function (Order $record, array $data, Action $action): void {
                try {
                    app(OrderStatusService::class)->change($record, OrderStatus::query()->findOrFail((int) $data['order_status_id']));
                } catch (CheckoutException $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();
                    $action->halt();
                }

                $record->refresh();

                Notification::make()->success()->title('Order status updated.')->send();
            });
    }
}
