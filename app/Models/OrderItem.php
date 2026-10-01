<?php

namespace App\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use PnShop\Catalog\Models\Product;
use PnShop\Money\MoneyCast;

/**
 * @property string $currency ISO 4217 code, same as the order
 */
class OrderItem extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'product_title',
        'product_sku',
        'quantity',
        'currency',
        'price',
        'discount_price',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => MoneyCast::class.':currency',
        'discount_price' => MoneyCast::class.':currency',
    ];

    /**
     * Get the order this item belongs to.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product for this item.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unitPrice(): Money
    {
        return $this->discount_price !== null && $this->discount_price->isPositive() ? $this->discount_price : $this->price;
    }

    public function lineTotal(): Money
    {
        return $this->unitPrice()->multipliedBy($this->quantity);
    }
}
