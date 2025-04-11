<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductAttributeValue extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
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
     */
    public function productAttribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class);
    }

    /**
     * Relationship with the Product model.
     * This defines a many-to-many relationship, where each attribute value can be associated with multiple products.
     * For example, if the attribute is 'Size', the values could be 'S', 'M', 'L', etc., and multiple products can have the same size.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }
}
