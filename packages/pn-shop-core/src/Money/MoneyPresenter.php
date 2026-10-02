<?php

namespace PnShop\Money;

use Brick\Money\Money;

/**
 * The shape money takes in storefront props and API responses.
 */
final class MoneyPresenter
{
    /**
     * @return array{amount: string, minor: int, currency: string, formatted: string}|null
     */
    public static function present(?Money $money, ?string $locale = null): ?array
    {
        if ($money === null) {
            return null;
        }

        return [
            'amount' => (string) $money->getAmount(),
            'minor' => $money->getMinorAmount()->toInt(),
            'currency' => $money->getCurrency()->getCurrencyCode(),
            'formatted' => $money->formatToLocale($locale ?? app()->getLocale()),
        ];
    }
}
