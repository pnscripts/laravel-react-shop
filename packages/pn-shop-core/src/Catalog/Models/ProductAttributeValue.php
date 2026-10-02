<?php

namespace PnShop\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use PnShop\Catalog\Factories\ProductAttributeValueFactory;
use PnShop\Localization\Concerns\Translatable;
use PnShop\Localization\Contracts\TranslatableModel;

class ProductAttributeValue extends Model implements TranslatableModel
{
    /** @use HasFactory<ProductAttributeValueFactory> */
    use HasFactory, SoftDeletes, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_attribute_id',
        'value', // e.g. 'Cotton', 'Yes', '40 W'
        'position',
        'translations_input',
    ];

    /**
     * The attributes that support translations.
     */

    /** @var list<string> */
    protected array $translatable = [
        'value',
    ];

    /**
     * Relationship with the ProductAttribute model.
     * This defines the inverse of the relationship, where each attribute value belongs to a specific attribute.
     * For example, if the attribute is 'Color', the values could be 'Red', 'Blue', etc.
     *
     * @return BelongsTo<ProductAttribute, $this>
     */
    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class);
    }

    /**
     * Relationship with the Product model.
     * This defines a many-to-many relationship, where each attribute value can be associated with multiple products.
     * For example, if the attribute is 'Size', the values could be 'S', 'M', 'L', etc., and multiple products can have the same size.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }

    protected static function newFactory(): ProductAttributeValueFactory
    {
        return ProductAttributeValueFactory::new();
    }
}
