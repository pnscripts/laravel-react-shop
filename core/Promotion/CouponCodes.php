<?php

namespace PnShop\Promotion;

use Illuminate\Support\Facades\DB;
use PnShop\Promotion\Models\Coupon;
use PnShop\Promotion\Models\Promotion;

/**
 * Generates unique, hard-to-guess coupon codes in bulk (e.g. one-use codes for a newsletter).
 */
final class CouponCodes
{
    /** Letters and digits without look-alikes (0/O, 1/I/L). */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * @return list<string> the new codes
     */
    public function generate(Promotion $promotion, int $count, string $prefix = '', ?int $usageLimit = 1, int $length = 8): array
    {
        $prefix = Coupon::normalize($prefix);
        $codes = [];

        DB::transaction(function () use ($promotion, $count, $prefix, $usageLimit, $length, &$codes) {
            while (count($codes) < $count) {
                $code = $prefix.$this->random($length);

                if (isset($codes[$code]) || Coupon::query()->where('code', $code)->exists()) {
                    continue;
                }

                Coupon::query()->create(['promotion_id' => $promotion->id, 'code' => $code, 'usage_limit' => $usageLimit]);
                $codes[$code] = true;
            }
        });

        activity('marketing')->performedOn($promotion)->withProperties(['count' => $count, 'prefix' => $prefix])->log('Coupon codes generated');

        return array_keys($codes);
    }

    private function random(int $length): string
    {
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $code;
    }
}
