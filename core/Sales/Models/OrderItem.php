<?php

namespace PnShop\Sales\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use PnShop\Catalog\Models\Product;
use PnShop\Money\MoneyCast;

/**
 * @property string $currency ISO 4217 code, same as the order
 * @property int $id
 * @property int $quantity
 * @property int $quantity_fulfilled units already shipped
 * @property Money $price
 * @property Money|null $sale_price
 * @property int|null $product_id
 * @property int|null $product_variant_id
 * @property string|null $product_title
 * @property string|null $product_sku
 * @property string|null $variant_label
 */
class OrderItem extends Model
{
    use SoftDeletes;

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
        'product_variant_id',
        'variant_label',
        'quantity',
        'quantity_fulfilled',
        'currency',
        'price',
        'sale_price',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'quantity_fulfilled' => 'integer',
        'price' => MoneyCast::class.':currency',
        'sale_price' => MoneyCast::class.':currency',
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
        // sale_price on an order line is what was actually charged, so it always wins when stored.
        return $this->sale_price !== null && $this->sale_price->isPositive() ? $this->sale_price : $this->price;
    }

    public function lineTotal(): Money
    {
        return $this->unitPrice()->multipliedBy($this->quantity);
    }
}
