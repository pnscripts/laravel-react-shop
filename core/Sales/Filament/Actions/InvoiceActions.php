<?php

namespace PnShop\Sales\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\URL;
use PnShop\Sales\Invoices\InvoiceService;
use PnShop\Sales\Models\Order;
use PnShop\Sales\States\OrderStatus;

class InvoiceActions
{
    /**
     * @return list<Action>
     */
    public static function all(): array
    {
        return [
            Action::make('issueInvoice')
                ->label('Issue invoice')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('gray')
                ->authorize(fn (Order $record) => auth('admin')->user()?->can('update', $record) ?? false)
                ->visible(fn (Order $record) => $record->invoice === null && $record->status !== OrderStatus::Cancelled)
                ->requiresConfirmation()
                ->modalDescription('Invoices are numbered in order and cannot be changed or deleted once issued.')
                ->action(function (Order $record): void {
                    $invoice = app(InvoiceService::class)->issue($record, auth('admin')->user());
                    $record->refresh();

                    Notification::make()->success()->title("Invoice {$invoice->number} issued.")->send();
                }),
            Action::make('viewInvoice')
                ->label(fn (Order $record) => 'Invoice '.$record->invoice?->number)
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('gray')
                ->visible(fn (Order $record) => $record->invoice !== null)
                // A short-lived signed link: staff are not storefront users.
                ->url(fn (Order $record) => $record->invoice === null ? null : URL::temporarySignedRoute('invoices.show', now()->addMinutes(30), ['invoice' => $record->invoice]), shouldOpenInNewTab: true),
        ];
    }
}
