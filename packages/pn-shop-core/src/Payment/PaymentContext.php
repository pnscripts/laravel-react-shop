<?php

namespace PnShop\Payment;

use App\Models\User;
use Brick\Money\Money;

/**
 * What is known when choosing a payment method: the amount, where the order goes and who pays.
 */
final readonly class PaymentContext
{
    public function __construct(
        public Money $total,
        public ?string $countryCode = null,
        public ?User $customer = null,
    ) {}
}
