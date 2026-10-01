<?php

namespace PnShop\Sales\Invoices;

use PnShop\Sales\Models\Invoice;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns an invoice into something to show or download. The core renders printable HTML;
 * an extension can bind a renderer that returns a PDF.
 */
interface InvoiceRenderer
{
    public function render(Invoice $invoice): Response;
}
