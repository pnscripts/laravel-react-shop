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
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Payment\PaymentContext;
use PnShop\Payment\PaymentOutcome;
use PnShop\Payment\PaymentService;
use PnShop\Sales\Checkout\CheckoutService;
use PnShop\Sales\Exceptions\CheckoutException;
use PnShop\Security\BotTrap;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private ShoppingCartService $cart,
        private CheckoutService $checkout,
        private PaymentService $payments,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        if ($this->cart->getCartItems()->isEmpty()) {
            return redirect()->route('cart.index')->with('error', __('Your cart is empty.'));
        }

        $user = $request->user();

        return Inertia::render('checkout/index', [
            'cart' => $this->cart->toArray(),
            'paymentMethods' => $this->payments
                ->availableMethods(new PaymentContext($this->cart->getFinalPrice(), customer: $user))
                ->map(fn (PaymentMethod $method) => ['id' => $method->id, 'name' => $method->name, 'description' => $method->description])
                ->values(),
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

    public function store(StoreCheckoutRequest $request): RedirectResponse|SymfonyResponse
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

        $payment = $this->payments->start($order);

        if ($payment->outcome === PaymentOutcome::Redirect && $payment->redirectUrl !== null) {
            return Inertia::location($payment->redirectUrl);
        }

        $redirect = redirect()->route('orders.show', $order)->with('success', __('Thank you! Your order has been placed.'));

        return $payment->outcome === PaymentOutcome::Failed ? $redirect->with('error', $payment->message) : $redirect;
    }
}
