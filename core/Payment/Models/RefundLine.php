<?php

namespace PnShop\Payment\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PnShop\Sales\Models\OrderItem;

/**
 * @property int $id
 * @property int $refund_id
 * @property int $order_item_id
 * @property int $quantity
 * @property int $amount minor units, in the refund's currency
 */
class RefundLine extends Model
{
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = ['refund_id', 'order_item_id', 'quantity', 'amount'];

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function money(string $currency): Money
    {
        return Money::ofMinor($this->amount, $currency);
    }
}
