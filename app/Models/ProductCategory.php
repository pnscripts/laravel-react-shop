<?php

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\HasSortOrder;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductCategory extends Model
{
    use HasFactory, SoftDeletes, HasTranslations, HasSlug, HasSortOrder;

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
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Relationship: Get the parent category.
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Scope: Filter categories by active status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $active
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereActive($query, bool $active = true)
    {
        return $query->where('is_active', $active);
    }

    /**
     * Scope: Filter categories by a specific parent category.
     */
    public function scopeByParent($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    /**
     * Scope: Filter categories by a specific slug.
     */
    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * Scope: Filter categories by a specific title.
     */
    public function scopeByTitle($query, $title)
    {
        return $query->where('title', 'like', "%$title%");
    }
}
