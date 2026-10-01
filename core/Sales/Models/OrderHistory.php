<?php

namespace PnShop\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * One entry in an order's timeline: a state change or a note, with who made it.
 *
 * @property int $id
 * @property int $order_id
 * @property string $field status|payment_status|fulfillment_status|note
 * @property string|null $from
 * @property string|null $to
 * @property string|null $note
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property Carbon $created_at
 */
class OrderHistory extends Model
{
    public const NOTE = 'note';

    public const UPDATED_AT = null;

    protected $table = 'order_history';

    /** @var list<string> */
    protected $fillable = ['order_id', 'field', 'from', 'to', 'note', 'actor_type', 'actor_id'];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The staff member or customer who made the change; null for the system.
     *
     * @return MorphTo<Model, $this>
     */
    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
