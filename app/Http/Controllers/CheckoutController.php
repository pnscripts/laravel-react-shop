<?php

namespace App\Http\Controllers;

use App\Http\Requests\Checkout\StoreCheckoutRequest;
use App\Models\PaymentMethod;
use App\Services\CheckoutService;
use App\Services\ShoppingCartService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(
        private ShoppingCartService $cart,
        private CheckoutService $checkout,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        if ($this->cart->getCartItems()->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $user = $request->user();

        return Inertia::render('checkout/index', [
            'cart' => $this->cart->toArray(),
            'paymentMethods' => PaymentMethod::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'description', 'type']),
            'defaults' => [
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
            ],
        ]);
    }

    public function store(StoreCheckoutRequest $request): RedirectResponse
    {
        try {
            $order = $this->checkout->place($request->validated(), $request->user());
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $recent = collect($request->session()->get('recent_order_ids', []))
            ->push($order->id)
            ->unique()
            ->values()
            ->all();

        $request->session()->put('recent_order_ids', $recent);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Thank you! Your order has been placed.');
    }
}
