<?php

namespace PnShop\Returns\Filament\Resources\Returns\Pages;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use PnShop\Returns\Filament\Resources\Returns\ReturnRequestResource;
use PnShop\Returns\Models\ReturnRequest;
use PnShop\Returns\Models\ReturnRequestLine;
use PnShop\Returns\ReturnService;
use PnShop\Returns\ReturnStatus;
use PnShop\Sales\Exceptions\OrderException;

/**
 * A return with the next steps as actions: approve or reject, receive (optionally back
 * into stock), refund what arrived, close.
 */
class ViewReturnRequest extends ViewRecord
{
    protected static string $resource = ReturnRequestResource::class;

    public function getTitle(): string
    {
        return 'Return '.$this->return()->number;
    }

    protected function getHeaderActions(): array
    {
        $note = fn (string $label = 'Note to the customer') => Textarea::make('note')->label($label)->rows(3)->maxLength(2000);

        return [
            Action::make('approve')
                ->icon(Heroicon::OutlinedCheck)
                ->visible(fn () => $this->can(ReturnStatus::Approved))
                ->schema([$note()->placeholder('E.g. where to send the parcel.')])
                ->action(fn (array $data) => $this->run(fn (ReturnService $returns) => $returns->approve($this->return(), $data['note'] ?? null, auth('admin')->user()), 'Return approved.')),
            Action::make('reject')
                ->icon(Heroicon::OutlinedXMark)
                ->color('danger')
                ->visible(fn () => $this->can(ReturnStatus::Rejected))
                ->schema([$note('Reason (sent to the customer)')->required()])
                ->action(fn (array $data) => $this->run(fn (ReturnService $returns) => $returns->reject($this->return(), $data['note'], auth('admin')->user()), 'Return rejected.')),
            Action::make('receive')
                ->label('Mark received')
                ->icon(Heroicon::OutlinedInboxArrowDown)
                ->visible(fn () => $this->can(ReturnStatus::Received))
                ->fillForm(fn () => ['received' => $this->return()->lines->mapWithKeys(fn (ReturnRequestLine $line) => [$line->id => $line->quantity])->all(), 'restock' => true])
                ->schema(fn () => [
                    ...$this->return()->lines->map(fn (ReturnRequestLine $line) => TextInput::make("received.{$line->id}")
                        ->label(($line->orderItem->product_title ?? 'Product').' — received')
                        ->integer()->minValue(0)->maxValue($line->quantity)->required())->all(),
                    Toggle::make('restock')->label('Put the received items back into stock'),
                ])
                ->action(fn (array $data) => $this->run(fn (ReturnService $returns) => $returns->receive($this->return(), array_map('intval', $data['received'] ?? []), (bool) $data['restock'], auth('admin')->user()), 'Return received.')),
            Action::make('refund')
                ->label('Refund received items')
                ->icon(Heroicon::OutlinedReceiptRefund)
                ->visible(fn () => $this->can(ReturnStatus::Refunded))
                ->requiresConfirmation()
                ->modalDescription('Refunds the price paid for the received items through the order\'s payment.')
                ->action(fn () => $this->run(fn (ReturnService $returns) => $returns->refund($this->return(), auth('admin')->user()), 'Return refunded.')),
            Action::make('close')
                ->icon(Heroicon::OutlinedArchiveBox)
                ->color('gray')
                ->visible(fn () => $this->can(ReturnStatus::Closed))
                ->schema([$note()])
                ->action(fn (array $data) => $this->run(fn (ReturnService $returns) => $returns->close($this->return(), $data['note'] ?? null, auth('admin')->user()), 'Return closed.')),
        ];
    }

    private function return(): ReturnRequest
    {
        /** @var ReturnRequest $return */
        $return = $this->getRecord();

        return $return->loadMissing('lines.orderItem');
    }

    private function can(ReturnStatus $to): bool
    {
        return $this->return()->status->canTransitionTo($to) && (auth('admin')->user()?->can('update', $this->return()) ?? false);
    }

    private function run(Closure $operation, string $success): void
    {
        try {
            $operation(app(ReturnService::class));
        } catch (OrderException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return;
        }

        $this->getRecord()->refresh();
        Notification::make()->success()->title($success)->send();
    }
}
