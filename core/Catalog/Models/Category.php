<?php

namespace PnShop\Catalog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use PnShop\Catalog\Factories\CategoryFactory;
use PnShop\Foundation\Concerns\HasSlug;
use PnShop\Foundation\Concerns\HasSortOrder;
use PnShop\Localization\Concerns\Translatable;
use PnShop\Localization\Contracts\TranslatableModel;

class Category extends Model implements TranslatableModel
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasSlug, HasSortOrder, SoftDeletes, Translatable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['title', 'slug', 'parent_id', 'sort_order', 'is_active'];

    /**
     * The attributes that support translations.
     */
    protected $table = 'product_categories';

    protected function translationForeignKey(): string
    {
        return 'product_category_id';
    }

    /** @var list<string> */
    protected array $translatable = ['title', 'slug'];

    /**
     * Relationship: Get all child categories (subcategories) of this category.
     *
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Relationship: Get the parent category.
     *
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * The attributes that belong to the product category.
     *
     * @return BelongsToMany<ProductAttribute, $this>
     */
    public function productAttributes(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttribute::class, 'product_attribute_product_category', 'product_category_id', 'product_attribute_id')
            ->withTimestamps();
    }

    /**
     * The products that belong to the product category.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_category_id');
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
