<?php

namespace PnShop\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PnShop\Customer\PostalAddress;

/**
 * A copy of the shipping or billing address taken when the order was placed.
 *
 * @property int $id
 * @property int $order_id
 * @property string $type shipping|billing
 */
class OrderAddress extends Model
{
    public const SHIPPING = 'shipping';

    public const BILLING = 'billing';

    /** @var list<string> */
    protected $fillable = ['order_id', 'type', ...PostalAddress::FIELDS];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function toPostalAddress(): PostalAddress
    {
        return PostalAddress::fromArray($this->only(PostalAddress::FIELDS));
    }
}
