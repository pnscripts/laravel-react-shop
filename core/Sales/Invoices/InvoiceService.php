<?php

namespace PnShop\Sales\Invoices;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PnShop\Foundation\NumberSequence;
use PnShop\Sales\Models\Invoice;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Models\OrderItem;
use PnShop\Sales\OrderWorkflow;
use PnShop\Settings\Settings;

/**
 * Issues an order's invoice (one per order) with the next number and a copy of the
 * seller, buyer, lines and totals.
 */
class InvoiceService
{
    public function __construct(
        private Settings $settings,
        private OrderWorkflow $workflow,
    ) {}

    public function issue(Order $order, ?Model $actor = null): Invoice
    {
        $existing = Invoice::query()->where('order_id', $order->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        $order->loadMissing(['items', 'billingAddress', 'shippingAddress']);

        $invoice = DB::transaction(function () use ($order) {
            $prefix = (string) $this->settings->get('sales.invoice_prefix');
            $digits = max(1, min(12, (int) $this->settings->get('sales.invoice_digits')));

            return Invoice::query()->create([
                'order_id' => $order->id,
                'number' => $prefix.str_pad((string) NumberSequence::next('invoice'), $digits, '0', STR_PAD_LEFT),
                'issued_at' => now(),
                'currency' => $order->currency,
                'locale' => $order->locale,
                'seller' => [
                    'name' => (string) ($this->settings->get('sales.invoice_legal_name') ?: $this->settings->get('store.name')),
                    'address' => $this->settings->get('store.address'),
                    'tax_number' => $this->settings->get('sales.invoice_tax_number'),
                    'email' => $this->settings->get('store.email'),
                    'phone' => $this->settings->get('store.phone'),
                    'footer' => $this->settings->get('sales.invoice_footer'),
                ],
                'buyer' => [
                    'name' => ($order->billingAddress ?? $order->shippingAddress)?->toPostalAddress()->fullName() ?: $order->name,
                    'lines' => ($order->billingAddress ?? $order->shippingAddress)?->toPostalAddress()->lines($order->locale) ?? array_values(array_filter([$order->name, $order->address])),
                    'email' => $order->email,
                ],
                'lines' => $order->items->map(fn (OrderItem $item) => [
                    'title' => trim($item->product_title.($item->variant_label ? " ({$item->variant_label})" : '')),
                    'sku' => $item->product_sku,
                    'quantity' => $item->quantity,
                    'unit' => $item->unitPrice()->getMinorAmount()->toInt(),
                    'total' => $item->lineTotal()->getMinorAmount()->toInt(),
                    'tax' => $item->tax_amount->getMinorAmount()->toInt(),
                ])->values()->all(),
                'totals' => [
                    'subtotal' => ($order->subtotal ?? $order->itemsTotal())->getMinorAmount()->toInt(),
                    'lines' => $order->totals ?? [],
                    'total' => $order->grandTotal()->getMinorAmount()->toInt(),
                ],
            ]);
        });

        $this->workflow->addNote($order, __('Invoice :number issued.', ['number' => $invoice->number]), $actor);

        return $invoice;
    }
}
