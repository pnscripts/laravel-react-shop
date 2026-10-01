<?php

namespace PnShop\Tax;

use Brick\Money\Money;

/**
 * One amount to tax: a cart line ("item:<variant id>") or the shipping ("shipping").
 */
final readonly class TaxableLine
{
    public function __construct(
        public string $key,
        public Money $amount,
        public ?int $taxClassId,
    ) {}
}
