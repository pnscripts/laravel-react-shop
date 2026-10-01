<?php

namespace PnShop\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PnShop\Sales\Models\OrderItem;

/**
 * @property int $id
 * @property int $shipment_id
 * @property int $order_item_id
 * @property int $quantity
 */
class ShipmentLine extends Model
{
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = ['shipment_id', 'order_item_id', 'quantity'];

    /**
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
