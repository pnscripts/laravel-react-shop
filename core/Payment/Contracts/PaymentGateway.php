<?php

namespace PnShop\Payment\Contracts;

use Brick\Money\Money;
use PnShop\Payment\Models\Payment;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Payment\PaymentContext;
use PnShop\Payment\PaymentResult;
use PnShop\Settings\SettingDefinition;

/**
 * A way to take payment: cash on delivery, bank transfer, a card provider, ...
 *
 * Merchants create payment methods (Admin → Store → Payment methods) that use a gateway
 * with their own settings. Gateways are registered with PaymentGatewayManager by core and
 * extensions; PnShop\Payment\Testing\PaymentGatewayContractTests checks an implementation.
 */
interface PaymentGateway
{
    /** Stable identifier stored on payment methods and payments, e.g. "bank_transfer". */
    public function code(): string;

    /** Name shown to staff when choosing a gateway. */
    public function label(): string;

    /**
     * Settings a payment method using this gateway is configured with (API keys, bank details, ...).
     *
     * @return list<SettingDefinition>
     */
    public function settings(): array;

    /** Whether the gateway can take this payment (currency, amount, country, ...). */
    public function isAvailable(PaymentContext $context, PaymentMethod $method): bool;

    /**
     * Start paying for the order. Returns paid, authorized, pending (the customer pays
     * later), redirect (to the provider's page) or failed.
     */
    public function initiate(Payment $payment, PaymentMethod $method): PaymentResult;

    public function supportsRefunds(): bool;

    /** Return money to the customer. Returns refunded or failed. */
    public function refund(Payment $payment, Money $amount, PaymentMethod $method): PaymentResult;

    /** What the customer should do next, shown on the order page (e.g. bank details). */
    public function instructions(Payment $payment, PaymentMethod $method): ?string;
}
