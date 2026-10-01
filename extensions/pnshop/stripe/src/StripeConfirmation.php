<?php

namespace PnShop\Plugins\Stripe;

use Illuminate\Support\Facades\DB;
use PnShop\Payment\Models\Payment;
use PnShop\Payment\PaymentResult;
use PnShop\Payment\PaymentService;

/**
 * Applies a Checkout Session's outcome to its payment once (the return visit and the
 * webhook may both arrive).
 */
class StripeConfirmation
{
    public function __construct(private PaymentService $payments) {}

    /**
     * @param  array<string, mixed>  $session  a Stripe Checkout Session object
     */
    public function apply(Payment $payment, array $session, string $source): void
    {
        if ((string) ($session['metadata']['payment_id'] ?? '') !== (string) $payment->id) {
            throw new StripeException('The Stripe session does not belong to this payment.');
        }

        DB::transaction(function () use ($payment, $session, $source) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if (! $payment->status->isOpen()) {
                return;
            }

            $amountMatches = (int) ($session['amount_total'] ?? -1) === $payment->amount->getMinorAmount()->toInt()
                && strtolower((string) ($session['currency'] ?? '')) === strtolower($payment->currency);

            $result = match (true) {
                ($session['payment_status'] ?? null) === 'paid' && $amountMatches => PaymentResult::paid((string) ($session['payment_intent'] ?? $session['id']), ['session' => $session['id'], 'source' => $source]),
                ($session['payment_status'] ?? null) === 'paid' => PaymentResult::failed(__('The amount paid on Stripe does not match the order.'), ['session' => $session['id']]),
                ($session['status'] ?? null) === 'expired' => PaymentResult::failed(__('The Stripe checkout expired before payment.'), ['session' => $session['id']]),
                default => null,
            };

            if ($result !== null) {
                $this->payments->apply($payment, $result, $source);
            }
        });
    }
}
