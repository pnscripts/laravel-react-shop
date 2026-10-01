<?php

namespace PnShop\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use PnShop\Sales\Factories\PaymentMethodFactory;

class PaymentMethod extends Model
{
    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'is_active',
    ];

    /**
     * Get the orders that use this payment method.
     *
     * @return HasMany<Order, $this>
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    protected static function newFactory(): PaymentMethodFactory
    {
        return PaymentMethodFactory::new();
    }
}
