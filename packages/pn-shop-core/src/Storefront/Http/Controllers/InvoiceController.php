<?php

namespace PnShop\Storefront\Http\Controllers;

use Illuminate\Http\Request;
use PnShop\Sales\Invoices\InvoiceRenderer;
use PnShop\Sales\Models\Invoice;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    /**
     * The customer who owns the order (or placed it in this session) can open its invoice;
     * staff open it through a short-lived signed link from the admin.
     */
    public function show(Request $request, Invoice $invoice, InvoiceRenderer $renderer): Response
    {
        $order = $invoice->order;
        $user = $request->user('web');

        $allowed = $request->hasValidSignature()
            || ($user !== null && $order !== null && (int) $order->user_id === (int) $user->id)
            || self::recentOrderIds($request)->contains($invoice->order_id);

        abort_unless($allowed, 403);

        return $renderer->render($invoice);
    }
}
