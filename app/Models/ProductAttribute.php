<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAttribute extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key',         // Internal reference like 'wifi', 'size', 'color'
        'label',       // Translatable label like 'WiFi', 'Size', 'Color'
        'type',        // Input type (e.g., text, boolean, select)
        'is_required', // Whether this attribute is mandatory
    ];

    /**
     * The attributes that should be translated.
     *
     * @var array
     */
    protected $translatable = ['label'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_required' => 'boolean',
    ];

    /**
     * Get the values associated with this product attribute.
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    /**
     * Get the categories that have this attribute.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class);
    }
}
