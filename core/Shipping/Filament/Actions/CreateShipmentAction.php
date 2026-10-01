<?php

namespace PnShop\Shipping\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use PnShop\Sales\Exceptions\OrderException;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Models\OrderItem;
use PnShop\Sales\States\OrderStatus;
use PnShop\Shipping\ShipmentService;

/**
 * Ship some or all of an order's remaining lines, with an optional tracking number.
 */
class CreateShipmentAction
{
    public static function make(): Action
    {
        $remaining = fn (Order $record) => $record->items->filter(fn (OrderItem $item) => $item->quantity_fulfilled < $item->quantity);

        return Action::make('createShipment')
            ->label('Create shipment')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->authorize(fn (Order $record) => auth('admin')->user()?->can('update', $record) ?? false)
            ->visible(fn (Order $record) => $record->status !== OrderStatus::Cancelled && $remaining($record)->isNotEmpty())
            ->fillForm(fn (Order $record) => [
                'quantities' => $remaining($record)->mapWithKeys(fn (OrderItem $item) => [$item->id => $item->quantity - $item->quantity_fulfilled])->all(),
            ])
            ->schema(fn (Order $record) => [
                Section::make('Items')
                    ->statePath('quantities')
                    ->schema($remaining($record)->map(fn (OrderItem $item) => TextInput::make((string) $item->id)
                        ->label(trim($item->product_title.($item->variant_label ? " ({$item->variant_label})" : '')))
                        ->helperText(($item->quantity - $item->quantity_fulfilled).' of '.$item->quantity.' left to ship')
                        ->integer()
                        ->minValue(0)
                        ->maxValue($item->quantity - $item->quantity_fulfilled))
                        ->values()
                        ->all()),
                TextInput::make('tracking_number')->maxLength(100),
                Textarea::make('note')->rows(2)->maxLength(1000),
            ])
            ->action(function (Order $record, array $data, Action $action): void {
                $quantities = array_filter(array_map('intval', $data['quantities'] ?? []));

                if ($quantities === []) {
                    Notification::make()->warning()->title('Choose at least one item to ship.')->send();
                    $action->halt();
                }

                try {
                    app(ShipmentService::class)->ship($record, $quantities, $data['tracking_number'] ?? null, $data['note'] ?? null, auth('admin')->user());
                } catch (OrderException $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();
                    $action->halt();
                }

                $record->refresh();

                Notification::make()->success()->title('Shipment created.')->send();
            });
    }
}
