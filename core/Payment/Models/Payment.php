<?php

namespace PnShop\Payment\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use PnShop\Money\MoneyCast;
use PnShop\Payment\PaymentState;
use PnShop\Sales\Models\Order;

/**
 * One attempt to pay for an order through a gateway, with its transactions.
 *
 * @property int $id
 * @property int $order_id
 * @property int|null $payment_method_id
 * @property string $gateway
 * @property PaymentState $status
 * @property string $currency
 * @property Money $amount
 * @property Money $refunded_amount
 * @property string|null $reference the gateway's id for the payment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Payment extends Model
{
    /** @var list<string> */
    protected $fillable = ['order_id', 'payment_method_id', 'gateway', 'status', 'currency', 'amount', 'refunded_amount', 'reference'];

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'pending', 'refunded_amount' => 0];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentState::class,
            'amount' => MoneyCast::class.':currency',
            'refunded_amount' => MoneyCast::class.':currency',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id')->withTrashed();
    }

    /**
     * @return HasMany<PaymentTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class)->orderBy('id');
    }

    /** Amount that can still be refunded. */
    public function refundable(): Money
    {
        return $this->amount->minus($this->refunded_amount);
    }
}
