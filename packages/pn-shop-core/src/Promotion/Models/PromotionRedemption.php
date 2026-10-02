<?php

namespace PnShop\Promotion\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use PnShop\Money\MoneyCast;
use PnShop\Sales\Models\Order;

/**
 * One use of a promotion (and coupon) by an order; counts towards usage limits.
 *
 * @property int $id
 * @property int $promotion_id
 * @property int|null $coupon_id
 * @property int $order_id
 * @property int|null $user_id
 * @property string|null $email
 * @property string $currency
 * @property Money $amount
 * @property Carbon|null $created_at
 */
class PromotionRedemption extends Model
{
    /** @var list<string> */
    protected $fillable = ['promotion_id', 'coupon_id', 'order_id', 'user_id', 'email', 'currency', 'amount'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['amount' => MoneyCast::class.':currency'];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
