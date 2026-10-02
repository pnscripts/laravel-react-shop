<?php

namespace PnShop\Sales\Invoices;

use Brick\Money\Money;
use PnShop\Sales\Models\Invoice;
use Symfony\Component\HttpFoundation\Response;

/**
 * A printable HTML invoice in the language the order was placed in.
 */
final class HtmlInvoiceRenderer implements InvoiceRenderer
{
    public function render(Invoice $invoice): Response
    {
        $previous = app()->getLocale();

        if ($invoice->locale !== null) {
            app()->setLocale($invoice->locale);
        }

        try {
            $locale = app()->getLocale();
            $money = fn (int $minor): string => Money::ofMinor($minor, $invoice->currency)->formatToLocale($locale);
            $issued = $invoice->issued_at->copy()->setTimezone(config()->string('app.timezone'));
            $issued->setLocale($locale);

            return response(view('pnshop::invoices.show', [
                'invoice' => $invoice,
                'money' => $money,
                'date' => $issued->isoFormat('LL'),
            ])->render());
        } finally {
            app()->setLocale($previous);
        }
    }
}
