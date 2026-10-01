<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PnShop\Money\MoneyPresenter;
use PnShop\Payment\Models\Refund;
use PnShop\Payment\PaymentService;
use PnShop\Sales\Models\Order;
use PnShop\Shipping\Models\Shipment;

class OrderController extends Controller
{
    public function show(Request $request, Order $order): Response
    {
        $recentIds = collect($request->session()->get('recent_order_ids', []));
        $isOwner = $request->user() && (int) $order->user_id === (int) $request->user()->id;
        $isRecent = $recentIds->contains($order->id);

        // A signed link from an order email: remember the order for this browser (invoice link, refresh).
        if (! $isOwner && ! $isRecent && $request->hasValidSignature()) {
            $request->session()->put('recent_order_ids', $recentIds->push($order->id)->unique()->values()->all());
            $isRecent = true;
        }

        abort_unless($isOwner || $isRecent, 403);

        $order->load(['items', 'paymentMethod', 'shippingAddress', 'billingAddress', 'shipments.lines', 'refunds', 'invoice']);

        return Inertia::render('orders/show', [
            'order' => [
                'id' => $order->id,
                'name' => $order->name,
                'email' => $order->email,
                'phone' => $order->phone,
                'shipping_address' => $order->shippingLines(),
                'billing_address' => $order->billingAddress?->toPostalAddress()->lines(),
                'number' => $order->number,
                ...$order->presentStates(),
                'payment_method' => $order->paymentMethod?->name,
                'payment_instructions' => app(PaymentService::class)->instructions($order),
                'shipping_method' => $order->shipping_method_name,
                'invoice' => $order->invoice === null ? null : ['number' => $order->invoice->number, 'url' => route('invoices.show', $order->invoice)],
                'refunds' => $order->refunds->where('status', Refund::COMPLETED)->values()->map(fn (Refund $refund) => [
                    'id' => $refund->id,
                    'amount' => MoneyPresenter::present($refund->amount),
                    'date' => $refund->created_at?->timezone(config('app.timezone'))->locale(app()->getLocale())->isoFormat('LL'),
                ]),
                'shipments' => $order->shipments->map(fn (Shipment $shipment) => [
                    'id' => $shipment->id,
                    'carrier' => $shipment->carrier_name,
                    'tracking_number' => $shipment->tracking_number,
                    'tracking_url' => $shipment->tracking_url,
                    'shipped_at' => $shipment->shipped_at?->timezone(config('app.timezone'))->locale(app()->getLocale())->isoFormat('LL'),
                    'items' => $shipment->lines->sum('quantity'),
                ]),
                'created_at' => $order->created_at?->timezone(config('app.timezone'))->locale(app()->getLocale())->isoFormat('LLL'),
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'title' => $item->product_title ?? 'Product',
                    'variant_label' => $item->variant_label,
                    'quantity' => $item->quantity,
                    'price' => MoneyPresenter::present($item->price),
                    'sale_price' => MoneyPresenter::present($item->sale_price),
                    'unit_price' => MoneyPresenter::present($item->unitPrice()),
                    'line_total' => MoneyPresenter::present($item->lineTotal()),
                ]),
                'totals' => $order->presentTotals(),
            ],
        ]);
    }
}
