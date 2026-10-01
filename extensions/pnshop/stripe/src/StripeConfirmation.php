<?php

namespace PnShop\Plugins\Stripe;

use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PnShop\Payment\Models\Payment;
use PnShop\Payment\PaymentResult;
use PnShop\Payment\PaymentService;
use PnShop\Sales\OrderWorkflow;

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
                $this->flagLatePayment($payment, $session, $source);

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

    /**
     * Money arrived for a payment that was already closed (e.g. the order was cancelled while
     * the customer was still on Stripe's page). It is recorded once and flagged in the order
     * history, so staff can refund it in Stripe; the order is not reopened.
     *
     * @param  array<string, mixed>  $session
     */
    private function flagLatePayment(Payment $payment, array $session, string $source): void
    {
        $reference = (string) ($session['payment_intent'] ?? $session['id'] ?? '');

        if (($session['payment_status'] ?? null) !== 'paid' || $payment->transactions()->where('type', 'late_payment')->where('reference', $reference)->exists()) {
            return;
        }

        $payment->transactions()->create([
            'type' => 'late_payment',
            'outcome' => 'paid',
            'currency' => $payment->currency,
            'amount' => Money::ofMinor((int) ($session['amount_total'] ?? 0), $payment->currency),
            'reference' => $reference,
            'message' => 'Paid on Stripe after the payment was closed.',
            'data' => ['session' => $session['id'] ?? null, 'source' => $source],
        ]);

        app(OrderWorkflow::class)->addNote(
            $payment->order()->firstOrFail(),
            __('Stripe received a payment (:reference) after this order\'s payment was closed. Refund it in the Stripe dashboard or reopen the order.', ['reference' => $reference]),
        );

        Log::warning('Stripe payment received for a closed payment.', ['payment_id' => $payment->id, 'reference' => $reference]);
    }
}
