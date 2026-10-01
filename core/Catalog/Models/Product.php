<?php

namespace PnShop\Catalog\Models;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use PnShop\Catalog\Factories\ProductFactory;
use PnShop\Catalog\ProductType;
use PnShop\Foundation\Concerns\HasSlug;
use PnShop\Inventory\InventoryService;
use PnShop\Localization\Concerns\Translatable;
use PnShop\Localization\Contracts\TranslatableModel;
use PnShop\Media\Concerns\HasMedia;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A catalog item. What is sold is a ProductVariant: a simple product has one default
 * variant; a variable product has one variant per option combination.
 *
 * For convenience `price`, `sale_price`, `sku`, `barcode`, `weight` and `stock` read and write
 * the default variant (stock through the inventory ledger), so simple products can be
 * created with `Product::create(['title' => ..., 'price' => '12.50', 'stock' => 5])`.
 *
 * @property int $id
 * @property ProductType $type
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property int $product_category_id
 * @property int|null $brand_id
 * @property bool $is_active
 * @property string|null $image legacy external image URL
 * @property-read Money|null $price
 * @property-read Money|null $sale_price
 * @property-read string|null $sku
 * @property-read string|null $barcode
 * @property-read int|null $stock units available across variants, null when not tracked
 */
class Product extends Model implements TranslatableModel
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasMedia, HasSlug, LogsActivity, SoftDeletes, Translatable;

    /** Attributes that belong to the default variant. */
    public const VARIANT_SHORTCUTS = ['price', 'sale_price', 'sku', 'barcode', 'weight'];

    /** @var list<string> */
    protected $fillable = [
        'type',
        'product_category_id',
        'brand_id',
        'title',
        'slug',
        'description',
        'is_active',
        'image',
        'price',
        'sale_price',
        'sku',
        'barcode',
        'weight',
        'stock',
    ];

    /** @var list<string> */
    protected array $translatable = ['title', 'slug', 'description'];

    /** @var array<string, mixed> values to write to the default variant on save */
    private array $pendingVariant = [];

    private ?int $pendingStock = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Visible in the store and has at least one active variant.
     *
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->whereHas('variants', fn (Builder $variants) => $variants->where('is_active', true));
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'product_category_id');
    }

    /**
     * Every category the product appears in, including the primary one.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product', 'product_id', 'product_category_id')
            ->withPivot('position');
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderByDesc('is_default')->orderBy('position')->orderBy('id');
    }

    /**
     * The options (Size, Color, ...) a variable product's variants combine.
     *
     * @return BelongsToMany<Option, $this>
     */
    public function options(): BelongsToMany
    {
        return $this->belongsToMany(Option::class)->withPivot('position')->orderByPivot('position');
    }

    /**
     * @return BelongsToMany<ProductAttributeValue, $this>
     */
    public function selectedAttributeValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttributeValue::class)->withTimestamps();
    }

    /**
     * The default variant (or the first one), from the loaded `variants` relation when available.
     */
    public function defaultVariant(): ?ProductVariant
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, ProductVariant> $variants */
        $variants = $this->exists ? $this->getRelationValue('variants') : collect();

        return $variants->firstWhere('is_default', true) ?? $variants->first();
    }

    /**
     * Active variants, the cheapest first.
     *
     * @return Collection<int, ProductVariant>
     */
    public function activeVariants(): Collection
    {
        return $this->getRelationValue('variants')->where('is_active', true)->values();
    }

    /**
     * The lowest unit price among active variants (what listings show as "from").
     */
    public function lowestPrice(): ?Money
    {
        return $this->activeVariants()
            ->map(fn (ProductVariant $variant) => $variant->unitPrice())
            ->sortBy(fn (Money $price) => $price->getMinorAmount()->toInt())
            ->first();
    }

    public function hasVaryingPrices(): bool
    {
        return $this->activeVariants()->map(fn (ProductVariant $variant) => (string) $variant->unitPrice()->getAmount())->unique()->count() > 1;
    }

    /**
     * @return Attribute<Money|null, mixed>
     */
    protected function price(): Attribute
    {
        return $this->variantShortcut('price');
    }

    /**
     * @return Attribute<Money|null, mixed>
     */
    protected function salePrice(): Attribute
    {
        return $this->variantShortcut('sale_price');
    }

    /**
     * @return Attribute<string|null, mixed>
     */
    protected function sku(): Attribute
    {
        return $this->variantShortcut('sku');
    }

    /**
     * @return Attribute<string|null, mixed>
     */
    protected function barcode(): Attribute
    {
        return $this->variantShortcut('barcode');
    }

    /**
     * @return Attribute<int|null, mixed>
     */
    protected function weight(): Attribute
    {
        return $this->variantShortcut('weight');
    }

    /**
     * @return Attribute<int|null, mixed>
     */
    protected function stock(): Attribute
    {
        return Attribute::make(
            get: function (): ?int {
                $inventory = app(InventoryService::class);
                $available = $this->activeVariants()->map(fn (ProductVariant $variant) => $inventory->available($variant));

                return $available->contains(null) ? null : (int) $available->sum();
            },
            set: function (mixed $value): array {
                $this->pendingStock = $value === null || $value === '' ? null : (int) $value;

                return [];
            },
        );
    }

    /**
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

    protected static function booted(): void
    {
        static::saved(function (Product $product): void {
            // The primary category is always one of the product's categories.
            if ($product->wasRecentlyCreated || $product->wasChanged('product_category_id')) {
                $product->categories()->syncWithoutDetaching([$product->product_category_id]);
            }

            $product->writeVariantShortcuts();
        });
    }

    /**
     * No native return type on purpose: Eloquent treats every method declared to return
     * Attribute as an accessor and calls it without arguments.
     *
     * @return Attribute<mixed, mixed>
     */
    private function variantShortcut(string $key)
    {
        return Attribute::make(
            get: fn () => $this->defaultVariant()?->getAttribute($key),
            set: function (mixed $value) use ($key): array {
                $this->pendingVariant[$key] = $value;

                return [];
            },
        );
    }

    private function writeVariantShortcuts(): void
    {
        if ($this->pendingVariant === [] && $this->pendingStock === null) {
            return;
        }

        $variant = $this->defaultVariant() ?? new ProductVariant(['product_id' => $this->id, 'is_default' => true, 'price' => 0]);
        $variant->fill($this->pendingVariant)->save();
        $this->pendingVariant = [];

        if ($this->pendingStock !== null) {
            app(InventoryService::class)->setOnHand($variant, $this->pendingStock);
            $this->pendingStock = null;
        }

        $this->unsetRelation('variants');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('catalog')
            ->logOnly(['title', 'type', 'is_active', 'product_category_id', 'brand_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
