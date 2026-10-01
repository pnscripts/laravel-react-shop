<?php

namespace PnShop\Promotion;

use Brick\Money\Money;

/**
 * A promotion that applied to the cart, kept in CartTotals::$meta['promotions'] (keyed by
 * promotion id) so checkout can record the redemption.
 */
final class AppliedPromotion
{
    public function __construct(
        public readonly int $promotionId,
        public readonly ?int $couponId,
        public readonly string $label,
        public Money $amount,
        public readonly bool $freeShipping = false,
    ) {}
}
