<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PnShop\Money\MoneyPresenter;
use PnShop\Sales\Models\Order;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $address = $user->addresses()->where('is_default_shipping', true)->first();

        return Inertia::render('dashboard', [
            'recentOrders' => $user->orders()->with(['orderStatus', 'items'])->limit(3)->get()->map(fn (Order $order) => [
                'id' => $order->id,
                'status' => $order->orderStatus ? __(ucfirst($order->orderStatus->name)) : null,
                'created_at' => $order->created_at?->timezone(config('app.timezone'))->locale(app()->getLocale())->isoFormat('LL'),
                'total' => MoneyPresenter::present($order->grandTotal()),
            ]),
            'defaultAddress' => $address?->toPostalAddress()->lines(),
        ]);
    }
}
