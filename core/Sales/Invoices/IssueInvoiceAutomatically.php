<?php

namespace PnShop\Sales\Invoices;

use PnShop\Sales\Events\OrderPlaced;
use PnShop\Sales\Events\OrderStateChanged;
use PnShop\Sales\States\PaymentStatus;
use PnShop\Settings\Settings;

/**
 * Issues the invoice when the order is placed or paid, as the "sales.invoice_on" setting says.
 */
class IssueInvoiceAutomatically
{
    public function __construct(private InvoiceService $invoices, private Settings $settings) {}

    public function handle(OrderPlaced|OrderStateChanged $event): void
    {
        $when = $this->settings->get('sales.invoice_on');

        $issue = match (true) {
            $event instanceof OrderPlaced => $when === 'placed',
            default => $when === 'paid' && $event->to === PaymentStatus::Paid,
        };

        if ($issue) {
            $this->invoices->issue($event->order);
        }
    }
}
