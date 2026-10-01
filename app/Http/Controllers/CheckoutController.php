<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Account\AddressesController;
use App\Http\Requests\Checkout\StoreCheckoutRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use PnShop\Cart\ShoppingCartService;
use PnShop\Customer\Models\CustomerAddress;
use PnShop\Customer\PostalAddress;
use PnShop\Sales\Checkout\CheckoutService;
use PnShop\Sales\Exceptions\CheckoutException;
use PnShop\Sales\Models\PaymentMethod;
use PnShop\Security\BotTrap;

class CheckoutController extends Controller
{
    public function __construct(
        private ShoppingCartService $cart,
        private CheckoutService $checkout,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        if ($this->cart->getCartItems()->isEmpty()) {
            return redirect()->route('cart.index')->with('error', __('Your cart is empty.'));
        }

        $user = $request->user();

        return Inertia::render('checkout/index', [
            'cart' => $this->cart->toArray(),
            'paymentMethods' => PaymentMethod::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'description', 'type']),
            'countries' => AddressesController::countryOptions(),
            'botTrap' => BotTrap::fields(),
            'savedAddresses' => $user?->addresses->map(fn (CustomerAddress $address) => [
                'id' => $address->id,
                ...$address->only(PostalAddress::FIELDS),
                'lines' => $address->toPostalAddress()->lines(),
                'is_default_shipping' => $address->is_default_shipping,
                'is_default_billing' => $address->is_default_billing,
            ])->values() ?? [],
            'defaults' => [
                'email' => $user->email ?? '',
                // Guests and customers without an address book start from their account name.
                'first_name' => Str::before((string) ($user->name ?? ''), ' '),
                'last_name' => Str::contains((string) ($user->name ?? ''), ' ') ? Str::after((string) $user?->name, ' ') : '',
                'phone' => $user->phone ?? '',
            ],
        ]);
    }

    public function store(StoreCheckoutRequest $request): RedirectResponse
    {
        try {
            $order = $this->checkout->place($request->validated(), $request->user());
        } catch (CheckoutException $e) {
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
            ->with('success', __('Thank you! Your order has been placed.'));
    }
}
