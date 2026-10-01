<?php

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\HasSortOrder;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends Model
{
    use HasFactory, HasSlug, HasSortOrder, HasTranslations, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['title', 'slug', 'parent_id', 'sort_order', 'is_active'];

    /**
     * The attributes that support translations.
     */
    protected array $translatable = ['title', 'slug'];

    /**
     * Relationship: Get all child categories (subcategories) of this category.
     *
     * @return HasMany<ProductCategory, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Relationship: Get the parent category.
     *
     * @return BelongsTo<ProductCategory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Scope: Filter categories by active status.
     *
     * @param  Builder  $query
     */
    public function scopeWhereActive($query, bool $active = true): Builder
    {
        return $query->where('is_active', $active);
    }

    /**
     * Scope: Filter categories by a specific parent category.
     */
    public function scopeByParent($query, $parentId): Builder
    {
        return $query->where('parent_id', $parentId);
    }

    /**
     * Scope: Filter categories by a specific slug.
     */
    public function scopeBySlug($query, $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    /**
     * Scope: Filter categories by a specific title.
     */
    public function scopeByTitle($query, $title): Builder
    {
        return $query->where('title', 'like', "%$title%");
    }

    /**
     * The attributes that belong to the product category.
     *
     * @return BelongsToMany<ProductAttribute, $this>
     */
    public function productAttributes(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttribute::class)
            ->withTimestamps();
    }

    /**
     * The products that belong to the product category.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
