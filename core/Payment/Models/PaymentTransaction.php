<?php

namespace PnShop\Payment\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PnShop\Money\MoneyCast;

/**
 * A single exchange with a gateway (initiate, capture, refund, ...) or a payment staff recorded.
 *
 * @property int $id
 * @property int $payment_id
 * @property string $type
 * @property string $outcome
 * @property Money|null $amount
 * @property string|null $reference
 * @property string|null $message
 * @property array<string, mixed>|null $data
 */
class PaymentTransaction extends Model
{
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = ['payment_id', 'type', 'outcome', 'currency', 'amount', 'reference', 'message', 'data', 'actor_type', 'actor_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class.':currency',
            'data' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
