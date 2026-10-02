<?php

namespace PnShop\Cart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PnShop\Catalog\Models\ProductVariant;

/**
 * @property int $id
 * @property int $cart_id
 * @property int $product_variant_id
 * @property int $quantity
 */
class CartLine extends Model
{
    /** @var list<string> */
    protected $fillable = ['cart_id', 'product_variant_id', 'quantity'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
