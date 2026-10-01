<?php

namespace PnShop\Plugins\Stripe\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PnShop\Payment\Models\Payment;
use PnShop\Payment\PaymentState;
use PnShop\Plugins\Stripe\StripeClient;
use PnShop\Plugins\Stripe\StripeConfirmation;
use PnShop\Plugins\Stripe\StripeException;
use PnShop\Plugins\Stripe\WebhookSignature;
use PnShop\Sales\OrderLinks;
use PnShop\Settings\Settings;

class StripeController
{
    /**
     * The customer comes back from Stripe. The session is read from Stripe's API (never
     * trusted from the URL) before the order is marked paid.
     *
     * Only the Checkout Session id proves the visitor is the customer: without it (anyone can
     * guess payment ids) the redirect goes to the plain order page, which shows the order only
     * to its customer or the browser that placed it, never to a signed link.
     */
    public function return(Request $request, int $payment, StripeClient $stripe, StripeConfirmation $confirmation): RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', '');
        $model = Payment::query()->where('gateway', 'stripe')->find($payment);

        if ($model === null || preg_match('/^cs_[A-Za-z0-9_]+$/', $sessionId) !== 1 || ! hash_equals((string) $model->reference, $sessionId)) {
            return $model === null
                ? redirect()->route('home')
                : redirect()->route('orders.show', ['order' => $model->order_id]);
        }

        $order = $model->order()->firstOrFail();

        try {
            $confirmation->apply($model, $stripe->get('checkout/sessions/'.$sessionId), 'return');
        } catch (StripeException $e) {
            report($e);
        }

        $paid = $model->fresh()?->status === PaymentState::Paid;

        return redirect()->to(OrderLinks::signedShow($order))
            ->with($paid ? 'success' : 'error', $paid ? __('Thank you! Your payment was received.') : __('The payment is not confirmed yet. If you completed it, it will appear shortly.'));
    }

    /**
     * Stripe's webhook: verified with the signing secret, then applied once.
     */
    public function webhook(Request $request, Settings $settings, StripeConfirmation $confirmation): Response
    {
        $payload = $request->getContent();

        if (! WebhookSignature::valid($payload, (string) $request->header('Stripe-Signature'), (string) $settings->get('plugin.pnshop_stripe.webhook_secret'))) {
            return response('Invalid signature', 400);
        }

        $event = json_decode($payload, true);
        $session = $event['data']['object'] ?? null;

        if (! is_array($event) || ! is_array($session) || ! str_starts_with((string) ($event['type'] ?? ''), 'checkout.session.')) {
            return response('Ignored', 200);
        }

        $payment = Payment::query()->where('gateway', 'stripe')->find((int) ($session['metadata']['payment_id'] ?? 0));

        if ($payment !== null && $payment->reference === ($session['id'] ?? null)) {
            $confirmation->apply($payment, $session, 'webhook');
        }

        return response('OK', 200);
    }
}
