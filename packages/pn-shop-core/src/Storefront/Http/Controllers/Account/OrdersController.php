<?php

namespace PnShop\Storefront\Http\Controllers\Account;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PnShop\Money\MoneyPresenter;
use PnShop\Sales\Models\Order;
use PnShop\Storefront\Http\Controllers\Controller;

class OrdersController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = $this->authenticatedCustomer($request)->orders()
            ->with(['items'])
            ->paginate(10)
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'number' => $order->number,
                ...$order->presentStates(),
                'created_at' => self::displayDate($order->created_at, 'LL'),
                'items_count' => $order->items->sum('quantity'),
                'total' => MoneyPresenter::present($order->grandTotal()),
            ]);

        return Inertia::render('account/orders', ['orders' => $orders]);
    }
}
