<?php

namespace PnShop\Foundation;

use Illuminate\Support\Facades\DB;

/**
 * Gap-free counters for documents that must be numbered in order (invoices, credit notes).
 * The row is locked for the rest of the surrounding transaction, so call it inside the
 * transaction that stores the numbered document.
 */
final class NumberSequence
{
    public static function next(string $name): int
    {
        return DB::transaction(function () use ($name) {
            DB::table('number_sequences')->insertOrIgnore(['name' => $name, 'value' => 0]);

            $value = (int) DB::table('number_sequences')->where('name', $name)->lockForUpdate()->value('value') + 1;

            DB::table('number_sequences')->where('name', $name)->update(['value' => $value]);

            return $value;
        });
    }
}
