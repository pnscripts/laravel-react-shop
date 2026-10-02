<?php

namespace PnShop\Money;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use PnShop\Localization\Localization;

/**
 * Stores money as an integer number of minor units (cents) and exposes Brick\Money\Money.
 *
 * Usage: `'price' => MoneyCast::class` (default currency) or `MoneyCast::class.':currency'`
 * to read the currency from another column. Decimal strings and numbers ("12.50") are accepted
 * when setting, so admin forms keep working; serialized values are decimal strings.
 *
 * @implements CastsAttributes<Money|null, Money|string|int|float|null>
 */
final class MoneyCast implements CastsAttributes, SerializesCastableAttributes
{
    public function __construct(private ?string $currencyColumn = null) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::ofMinor((int) $value, $this->currency($attributes));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $currency = $this->currency($attributes);

        if ($value instanceof Money) {
            if ($value->getCurrency()->getCurrencyCode() !== $currency) {
                throw new InvalidArgumentException("Cannot store {$value->getCurrency()} in a {$currency} column [{$key}].");
            }

            return $value->getMinorAmount()->toInt();
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Invalid money amount for [{$key}].");
        }

        $amount = is_float($value) ? BigNumber::of(number_format($value, 10, '.', '')) : BigNumber::of((string) $value);

        return Money::of($amount, $currency, roundingMode: RoundingMode::HalfUp)->getMinorAmount()->toInt();
    }

    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value instanceof Money ? (string) $value->getAmount() : null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function currency(array $attributes): string
    {
        $code = $this->currencyColumn !== null ? ($attributes[$this->currencyColumn] ?? null) : null;

        return is_string($code) && $code !== '' ? $code : app(Localization::class)->defaultCurrency()->code;
    }
}
