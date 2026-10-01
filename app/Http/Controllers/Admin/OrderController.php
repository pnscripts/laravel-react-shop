<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Services\OrderStatusService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        $orders = Order::query()
            ->with(['orderStatus', 'paymentMethod'])
            ->latest()
            ->paginate(15)
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'name' => $order->name,
                'email' => $order->email,
                'status_id' => $order->order_status_id,
                'status' => $order->orderStatus?->name,
                'payment_method' => $order->paymentMethod?->name,
                'created_at' => $order->created_at?->toDayDateTimeString(),
            ]);

        return Inertia::render('admin/orders/index', [
            'orders' => $orders,
            'statuses' => OrderStatus::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateOrderStatusRequest $request, Order $order, OrderStatusService $statuses): RedirectResponse
    {
        try {
            $statuses->change($order, OrderStatus::findOrFail($request->validated('order_status_id')));
        } catch (CheckoutException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Order status updated.');
    }
}
