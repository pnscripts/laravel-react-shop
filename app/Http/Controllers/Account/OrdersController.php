<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PnShop\Money\MoneyPresenter;

class OrdersController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = $request->user()->orders()
            ->with(['orderStatus', 'items'])
            ->paginate(10)
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'status' => $order->orderStatus ? __(ucfirst($order->orderStatus->name)) : null,
                'created_at' => $order->created_at?->timezone(config('app.timezone'))->locale(app()->getLocale())->isoFormat('LL'),
                'items_count' => $order->items->sum('quantity'),
                'total' => MoneyPresenter::present($order->itemsTotal()),
            ]);

        return Inertia::render('account/orders', ['orders' => $orders]);
    }
}
