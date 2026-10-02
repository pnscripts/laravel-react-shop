<?php

namespace PnShop\Storefront\Http\Controllers\Account;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PnShop\Money\MoneyPresenter;
use PnShop\Sales\Models\Order;
use PnShop\Storefront\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $this->authenticatedCustomer($request);
        $address = $user->addresses()->where('is_default_shipping', true)->first();

        return Inertia::render('dashboard', [
            'recentOrders' => $user->orders()->with(['items'])->limit(3)->get()->map(fn (Order $order) => [
                'id' => $order->id,
                'number' => $order->number,
                ...$order->presentStates(),
                'created_at' => self::displayDate($order->created_at, 'LL'),
                'total' => MoneyPresenter::present($order->grandTotal()),
            ]),
            'defaultAddress' => $address?->toPostalAddress()->lines(),
        ]);
    }
}
