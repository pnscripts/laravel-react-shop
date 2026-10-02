<?php

namespace PnShop\Payment\Gateways;

use Brick\Money\Money;
use PnShop\Payment\Contracts\PaymentGateway;
use PnShop\Payment\Models\Payment;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Payment\PaymentContext;
use PnShop\Payment\PaymentResult;

/**
 * A payment taken outside the shop (cash, bank transfer). The order waits as unpaid
 * until staff record the payment; refunds are recorded the same way.
 */
abstract class ManualGateway implements PaymentGateway
{
    public function isAvailable(PaymentContext $context, PaymentMethod $method): bool
    {
        return true;
    }

    public function initiate(Payment $payment, PaymentMethod $method): PaymentResult
    {
        return PaymentResult::pending();
    }

    public function supportsRefunds(): bool
    {
        return true;
    }

    public function refund(Payment $payment, Money $amount, PaymentMethod $method): PaymentResult
    {
        return PaymentResult::refunded(data: ['manual' => true]);
    }

    public function instructions(Payment $payment, PaymentMethod $method): ?string
    {
        $text = trim((string) $method->setting('instructions'));

        return $text === '' ? null : strtr($text, [
            ':amount' => $payment->amount->formatToLocale(app()->getLocale()),
            ':order' => (string) $payment->order?->number,
        ]);
    }
}
