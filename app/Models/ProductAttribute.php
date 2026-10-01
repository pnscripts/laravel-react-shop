<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use PnShop\Localization\Concerns\Translatable;
use PnShop\Localization\Contracts\TranslatableModel;

class ProductAttribute extends Model implements TranslatableModel
{
    use HasFactory, SoftDeletes, Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',         // Internal reference like 'wifi', 'size', 'color'
        'label',       // Translatable label like 'WiFi', 'Size', 'Color'
        'type',        // Input type (e.g., text, boolean, select)
        'is_required', // Whether this attribute is mandatory
    ];

    /**
     * The attributes that should be translated.
     */
    protected array $translatable = ['label'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_required' => 'boolean',
    ];

    /**
     * Get the values associated with this product attribute.
     *
     * @return HasMany<ProductAttributeValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    /**
     * Get the categories that have this attribute.
     *
     * @return BelongsToMany<ProductCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class);
    }
}
