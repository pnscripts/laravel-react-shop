<?php

namespace PnShop\Cart\Totals;

use Brick\Money\Money;

/**
 * One row of the totals breakdown between the subtotal and the grand total,
 * such as shipping, a discount (negative) or tax.
 */
final readonly class TotalLine
{
    /**
     * @param  bool  $included  Already contained in the prices (e.g. VAT in gross prices): shown, not added.
     */
    public function __construct(
        public string $code,
        public string $label,
        public Money $amount,
        public bool $included = false,
    ) {}
}
