<?php

namespace PnShop\Returns\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PnShop\Sales\Models\OrderItem;

/**
 * @property int $id
 * @property int $return_request_id
 * @property int $order_item_id
 * @property int $quantity units the customer asked to return
 * @property int $quantity_received units that arrived back
 */
class ReturnRequestLine extends Model
{
    /** @var list<string> */
    protected $fillable = ['return_request_id', 'order_item_id', 'quantity', 'quantity_received'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['quantity' => 'integer', 'quantity_received' => 'integer'];
    }

    /**
     * @return BelongsTo<ReturnRequest, $this>
     */
    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
