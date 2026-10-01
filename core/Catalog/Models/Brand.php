<?php

namespace PnShop\Catalog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use PnShop\Catalog\Factories\BrandFactory;
use PnShop\Localization\Concerns\Translatable;
use PnShop\Localization\Contracts\TranslatableModel;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_active
 */
class Brand extends Model implements TranslatableModel
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory, SoftDeletes, Translatable;

    /** @var list<string> */
    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    /** @var list<string> */
    protected array $translatable = ['name', 'slug', 'description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Brand $brand): void {
            $base = Str::slug($brand->getAttributes()['slug'] ?? '' ?: $brand->getAttributes()['name'] ?? '') ?: 'brand';
            $slug = $base;

            for ($i = 2; static::query()->withTrashed()->where('slug', $slug)->whereKeyNot($brand->getKey())->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }

            $brand->setAttribute('slug', $slug);
        });
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected static function newFactory(): BrandFactory
    {
        return BrandFactory::new();
    }
}
