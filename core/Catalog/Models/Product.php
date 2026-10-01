<?php

namespace PnShop\Catalog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use PnShop\Catalog\Factories\ProductFactory;
use PnShop\Foundation\Concerns\HasSlug;
use PnShop\Localization\Concerns\Translatable;
use PnShop\Localization\Contracts\TranslatableModel;
use PnShop\Money\MoneyCast;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Product extends Model implements TranslatableModel
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasSlug, LogsActivity, SoftDeletes, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
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

    /** @var list<string> */
    protected array $translatable = [
        'title',
        'slug',
        'description',
    ];

    protected $casts = [
        'attribute_values' => 'array',
        'price' => MoneyCast::class,
        'discount_price' => MoneyCast::class,
        'is_active' => 'boolean',
    ];

    /**
     * Scope: only products that are publicly visible.
     */
    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Get the category this product belongs to.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'product_category_id');
    }

    /**
     * Get the attributes associated with this product.
     * This is a many-to-many relationship, where each product can have multiple attributes and each attribute can belong to multiple products.
     *
     * @return BelongsToMany<ProductAttributeValue, $this>
     */
    public function selectedAttributeValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttributeValue::class)->withTimestamps();
    }

    /**
     * Get the attributes with their values for this product.
     * This method retrieves the attributes defined in the product's category and their corresponding values.
     * It also checks which values have been selected for this product.
     *
     * @return Collection<int, array{attribute: string, value: string|null}>
     */
    public function getProductAttributesWithValues(): Collection
    {
        $categoryAttributes = $this->category
            ->productAttributes()
            ->with('values')
            ->get();

        $selectedValueIds = $this->selectedAttributeValues->pluck('id')->toArray();

        return $categoryAttributes->map(function (ProductAttribute $attribute) use ($selectedValueIds) {
            $selectedValue = $attribute->values->first(fn (ProductAttributeValue $value) => in_array($value->id, $selectedValueIds));

            return [
                'attribute' => $attribute->label,
                'value' => $selectedValue?->value,
            ];
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('catalog')
            ->logOnly(['title', 'price', 'discount_price', 'stock', 'is_active', 'sku', 'product_category_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
