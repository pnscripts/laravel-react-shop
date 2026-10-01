<?php

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Product extends Model
{
    use HasFactory, HasSlug, HasTranslations, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_category_id',
        'title',
        'slug',
        'description',
        'price',
        'discount_price',
        'stock',
        'is_active',
        'sku',
        'barcode',
        'image',
    ];

    /**
     * The attributes that support translations.
     */
    protected $translatable = [
        'title',
        'slug',
        'description',
    ];

    protected $casts = [
        'attribute_values' => 'array',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Scope: only products that are publicly visible.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the category this product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * Get the attributes associated with this product.
     * This is a many-to-many relationship, where each product can have multiple attributes and each attribute can belong to multiple products.
     */
    public function selectedAttributeValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttributeValue::class)->withTimestamps();
    }

    /**
     * Get the attributes with their values for this product.
     * This method retrieves the attributes defined in the product's category and their corresponding values.
     * It also checks which values have been selected for this product.
     */
    public function getProductAttributesWithValues(): Collection
    {
        $categoryAttributes = $this->category
            ->productAttributes()
            ->with('values')
            ->get();

        $selectedValueIds = $this->selectedAttributeValues->pluck('id')->toArray();

        return $categoryAttributes->map(function ($attribute) use ($selectedValueIds) {
            $selectedValue = $attribute->values->firstWhere(fn ($value) => in_array($value->id, $selectedValueIds));

            return [
                'attribute' => $attribute->label, // label should be translatable
                'value' => $selectedValue?->label, // label should be translatable
            ];
        });
    }
}
