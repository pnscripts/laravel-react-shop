<?php

namespace PnShop\Api\Http\Controllers\Store;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use PnShop\Api\Http\Controllers\ApiController;
use PnShop\Api\Http\Problem;
use PnShop\Api\Http\Resources\OrderPresenter;
use PnShop\Cart\ShoppingCartService;
use PnShop\Customer\PostalAddress;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Payment\PaymentContext;
use PnShop\Payment\PaymentOutcome;
use PnShop\Payment\PaymentService;
use PnShop\Sales\Checkout\CheckoutRules;
use PnShop\Sales\Checkout\CheckoutService;
use PnShop\Sales\OrderLinks;
use PnShop\Shipping\ShippingQuote;
use PnShop\Shipping\ShippingRequest;
use PnShop\Shipping\ShippingService;

class CheckoutController extends ApiController
{
    public function __construct(
        private ShoppingCartService $cart,
        private CheckoutService $checkout,
        private PaymentService $payments,
        private ShippingService $shipping,
    ) {}

    /**
     * Payment methods
     *
     * The payment methods available for the current cart.
     *
     * @return array<string, mixed>
     */
    public function paymentMethods(Request $request): array
    {
        return ['data' => $this->payments
            ->availableMethods(new PaymentContext($this->cart->getFinalPrice(), $request->string('country_code')->upper()->toString() ?: null, $this->customer($request)))
            ->map(fn (PaymentMethod $method) => ['id' => $method->id, 'name' => $method->name, 'description' => $method->description])
            ->values()
            ->all()];
    }

    /**
     * Shipping options and totals
     *
     * Delivery options for an address and the cart totals with the chosen (or cheapest first)
     * option. Whether the cart needs shipping at all is in `shipping_required`.
     *
     * @return array<string, mixed>
     */
    public function quote(Request $request): array
    {
        $data = $request->validate([
            'country_code' => ['required', 'string', 'size:2'],
            'postcode' => ['nullable', 'string', 'max:32'],
            'shipping_method_id' => ['nullable', 'integer'],
        ]);

        $address = PostalAddress::fromArray($data);
        $quotes = $this->shipping->quotes(new ShippingRequest($this->cart->getCartItems(), $this->cart->getTotalPrice(), $address->country_code, $address->postcode, $this->customer($request)));
        $selected = $quotes->first(fn (ShippingQuote $quote) => $quote->method->id === (int) ($data['shipping_method_id'] ?? 0)) ?? $quotes->first();

        return ['data' => [
            'shipping_required' => $this->shipping->isRequired(),
            'options' => $quotes->map(fn (ShippingQuote $quote) => $quote->toArray())->values()->all(),
            'selected' => $selected?->method->id,
            'totals' => $this->cart->totals([
                'shipping_address' => $address,
                'shipping_method' => $selected?->method,
                'user' => $this->customer($request),
            ])->toArray(),
        ]];
    }

    /**
     * Place the order
     *
     * Turns the cart into an order and starts the payment. Send an Idempotency-Key header so
     * a retried request cannot place the order twice. When `payment.outcome` is `redirect`,
     * send the customer to `payment.redirect_url`. Guests read the order later through
     * `links.order` (a signed URL); customers through /account/orders.
     */
    public function store(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), CheckoutRules::rules(), [], CheckoutRules::attributes())->validate();
        $customer = $this->customer($request);

        if ($customer !== null && ! $customer->hasVerifiedEmail()) {
            return Problem::response(403, 'email_not_verified', __('Verify your email address before ordering.'));
        }

        $order = $this->checkout->place($data, $customer);
        $payment = $this->payments->start($order);

        $order->load(OrderPresenter::RELATIONS);

        return response()->json([
            'data' => OrderPresenter::detail($order),
            'payment' => [
                'outcome' => $payment->outcome->value,
                'redirect_url' => $payment->outcome === PaymentOutcome::Redirect ? $payment->redirectUrl : null,
                'message' => $payment->outcome === PaymentOutcome::Failed ? $payment->message : null,
            ],
            'links' => ['order' => URL::temporarySignedRoute('api.store.orders.show', now()->addDays(OrderLinks::days()), ['order' => $order])],
        ], 201);
    }
}
