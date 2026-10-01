<?php

namespace PnShop\Shipping\Carriers;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Every non-empty line of a rate table must be "threshold: price".
 */
final class RateTableRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach (preg_split('/\R/', (string) $value) ?: [] as $number => $line) {
            if (trim($line) !== '' && ! preg_match(RateTable::FORMAT, $line)) {
                $fail(__('Line :line must look like "1000: 5.00".', ['line' => $number + 1]));

                return;
            }
        }
    }
}
