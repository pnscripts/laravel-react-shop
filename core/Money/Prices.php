<?php

namespace PnShop\Money;

use Brick\Money\Money;

final class Prices
{
    /**
     * A sale price applies only when it is set, positive and lower than the regular price.
     */
    public static function isSale(Money $price, ?Money $salePrice): bool
    {
        return $salePrice !== null && $salePrice->isPositive() && $salePrice->isLessThan($price);
    }

    /**
     * The price one unit sells for.
     */
    public static function effective(Money $price, ?Money $salePrice): Money
    {
        return self::isSale($price, $salePrice) && $salePrice !== null ? $salePrice : $price;
    }
}
