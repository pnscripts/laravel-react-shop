<?php

namespace PnShop\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use PnShop\Sales\Factories\OrderStatusFactory;

class OrderStatus extends Model
{
    /** @use HasFactory<OrderStatusFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Get the orders that have this status.
     *
     * @return HasMany<Order, $this>
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    protected static function newFactory(): OrderStatusFactory
    {
        return OrderStatusFactory::new();
    }
}
