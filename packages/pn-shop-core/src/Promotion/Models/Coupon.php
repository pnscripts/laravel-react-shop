<?php

namespace PnShop\Promotion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A code that unlocks a promotion. Codes are stored upper-case and matched ignoring case.
 *
 * @property int $id
 * @property int $promotion_id
 * @property string $code
 * @property bool $is_active
 * @property int|null $usage_limit
 * @property int $times_used
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Coupon extends Model
{
    /** @var list<string> */
    protected $fillable = ['promotion_id', 'code', 'is_active', 'usage_limit'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'usage_limit' => 'integer', 'times_used' => 'integer'];
    }

    public static function normalize(string $code): string
    {
        return mb_strtoupper(trim($code));
    }

    protected static function booted(): void
    {
        static::saving(fn (Coupon $coupon) => $coupon->code = self::normalize($coupon->code));
    }

    /**
     * @return BelongsTo<Promotion, $this>
     */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class)->withTrashed();
    }
}
