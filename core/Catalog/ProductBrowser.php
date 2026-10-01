<?php

namespace PnShop\Catalog;

use Illuminate\Database\Eloquent\Builder;
use PnShop\Catalog\Models\Brand;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Product;
use PnShop\Localization\Localization;

/**
 * The product listing filters shared by the shop page and the Store API: category (with its
 * subcategories), brand, attribute values and a title search, over active products.
 *
 * An unknown category or brand slug matches nothing rather than being ignored.
 */
final class ProductBrowser
{
    /**
     * @param  array<int, list<int>>  $attributeFilters  attribute id => chosen value ids
     */
    private function __construct(
        public readonly ?Category $category,
        public readonly ?Brand $brand,
        private readonly bool $matchesNothing,
        public readonly array $attributeFilters,
        public readonly string $search,
    ) {}

    /**
     * @param  array<string, mixed>  $input  category (slug), brand (slug), filter (attribute id => value ids), q
     */
    public static function fromInput(array $input): self
    {
        $categorySlug = is_string($input['category'] ?? null) ? $input['category'] : '';
        $brandSlug = is_string($input['brand'] ?? null) ? $input['brand'] : '';

        $category = $categorySlug !== '' ? Category::query()->active()->whereTranslated('slug', $categorySlug)->first() : null;
        $brand = $brandSlug !== '' ? Brand::query()->active()->whereTranslated('slug', $brandSlug)->first() : null;

        $attributeFilters = collect(is_array($input['filter'] ?? null) ? $input['filter'] : [])
            ->mapWithKeys(fn (mixed $values, mixed $attributeId) => [(int) $attributeId => array_values(array_filter(array_map('intval', (array) $values)))])
            ->filter()
            ->all();

        return new self(
            $category,
            $brand,
            ($categorySlug !== '' && $category === null) || ($brandSlug !== '' && $brand === null),
            $attributeFilters,
            is_string($input['q'] ?? null) ? trim(mb_substr($input['q'], 0, 100)) : '',
        );
    }

    /**
     * Active products in the chosen category and brand (the set facets are computed from).
     *
     * @return Builder<Product>
     */
    public function base(): Builder
    {
        return Product::query()
            ->active()
            ->when($this->matchesNothing, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($this->category !== null, fn (Builder $query) => $query->whereHas('categories', fn (Builder $categories) => $categories->whereKey($this->category?->subtreeIds() ?? [])))
            ->when($this->brand !== null, fn (Builder $query) => $query->where('brand_id', $this->brand?->id));
    }

    /**
     * base() narrowed by attribute values and the search text.
     *
     * @return Builder<Product>
     */
    public function query(): Builder
    {
        return $this->base()
            // Values of one attribute are alternatives (OR); different attributes narrow down (AND).
            ->tap(function (Builder $query): void {
                foreach ($this->attributeFilters as $valueIds) {
                    $query->whereHas('selectedAttributeValues', fn (Builder $values) => $values->whereIn('product_attribute_values.id', $valueIds));
                }
            })
            // Titles in the default language live on the products table, others in translations
            // (which fall back to the default title when missing).
            ->when($this->search !== '', function (Builder $query): void {
                $pattern = '%'.addcslashes($this->search, '%_\\').'%';
                $locale = app()->getLocale();

                // whereLike ignores case on every database (PostgreSQL's LIKE does not).
                $query->where(fn (Builder $query) => $query
                    ->whereLike('products.title', $pattern)
                    ->when($locale !== app(Localization::class)->defaultLocale(), fn (Builder $query) => $query
                        ->orWhereHas('translations', fn (Builder $translations) => $translations->where('locale', $locale)->whereLike('title', $pattern))));
            });
    }
}
