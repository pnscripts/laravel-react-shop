<?php

namespace PnShop\Shipping\Carriers;

/**
 * A table of "threshold: price" lines, e.g. for weight in grams:
 *
 *     1000: 5.00
 *     5000: 9.50
 *
 * Weight tables use the first row whose threshold is at or above the value ("up to");
 * price tables use the last row whose threshold is at or below it ("from").
 */
final class RateTable
{
    public const FORMAT = '/^\s*(\d+(?:[.,]\d+)?)\s*[:=]\s*(\d+(?:[.,]\d{1,4})?)\s*$/';

    /**
     * @return list<array{float, string}> [threshold, price] sorted by threshold
     */
    public static function parse(?string $table): array
    {
        $rows = [];

        foreach (preg_split('/\R/', (string) $table) ?: [] as $line) {
            if (preg_match(self::FORMAT, $line, $match)) {
                $rows[] = [(float) str_replace(',', '.', $match[1]), str_replace(',', '.', $match[2])];
            }
        }

        usort($rows, fn (array $a, array $b) => $a[0] <=> $b[0]);

        return $rows;
    }

    /** Price of the first row whose threshold is >= value. */
    public static function upTo(?string $table, float $value): ?string
    {
        foreach (self::parse($table) as [$threshold, $price]) {
            if ($value <= $threshold) {
                return $price;
            }
        }

        return null;
    }

    /** Price of the last row whose threshold is <= value. */
    public static function from(?string $table, float $value): ?string
    {
        $found = null;

        foreach (self::parse($table) as [$threshold, $price]) {
            if ($value >= $threshold) {
                $found = $price;
            }
        }

        return $found;
    }
}
