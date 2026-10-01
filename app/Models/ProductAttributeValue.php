<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAttributeValue extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_attribute_id',
        'value', // The value for this attribute (e.g., 'True', 'S', 'blue', '123')
    ];

    /**
     * The attributes that support translations.
     */
    protected $translatable = [
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
}
