<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function show(Request $request, Order $order): Response
    {
        $recentIds = collect($request->session()->get('recent_order_ids', []));
        $isOwner = $request->user() && (int) $order->user_id === (int) $request->user()->id;
        $isRecent = $recentIds->contains($order->id);

        abort_unless($isOwner || $isRecent || $request->user()?->isAdmin(), 403);

        $order->load(['items', 'orderStatus', 'paymentMethod']);

        return Inertia::render('orders/show', [
            'order' => [
                'id' => $order->id,
                'name' => $order->name,
                'email' => $order->email,
                'phone' => $order->phone,
                'address' => $order->address,
                'status' => $order->orderStatus?->name,
                'payment_method' => $order->paymentMethod?->name,
                'created_at' => $order->created_at?->toDayDateTimeString(),
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'title' => $item->product_title ?? 'Product',
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'discount_price' => $item->discount_price,
                    'line_total' => ($item->discount_price ?: $item->price) * $item->quantity,
                ]),
                'total' => $order->items->sum(fn ($item) => ($item->discount_price ?: $item->price) * $item->quantity),
            ],
        ]);
    }
}
