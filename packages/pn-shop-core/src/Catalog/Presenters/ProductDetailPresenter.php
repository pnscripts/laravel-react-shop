<?php

namespace PnShop\Catalog\Presenters;

use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Option;
use PnShop\Catalog\Models\OptionValue;
use PnShop\Catalog\Models\Product;
use PnShop\Catalog\Models\ProductVariant;
use PnShop\Media\MediaPresenter;
use PnShop\Media\Models\Media;
use PnShop\Money\MoneyPresenter;

/**
 * The product page shape (storefront product page and Store API): gallery, options,
 * purchasable variants with prices and stock, breadcrumbs and attributes.
 */
final class ProductDetailPresenter
{
    /**
     * Load what present() needs; only active variants are kept.
     */
    public static function load(Product $product): Product
    {
        return $product->load([
            'category:id,title,slug,parent_id,_lft,_rgt',
            'brand:id,name,slug',
            'selectedAttributeValues',
            'media',
            'options.values',
            'variants' => fn ($variants) => $variants->where('is_active', true)->with(['optionValues', 'stockLevels']),
        ]);
    }

    /**
     * The category and its ancestors, root first.
     *
     * @return list<Category>
     */
    public static function trail(Product $product): array
    {
        return $product->category
            ? array_values(Category::query()->whereAncestorOf($product->category, andSelf: true)->defaultOrder()->get()->all())
            : [];
    }

    /**
     * @param  list<Category>  $trail  from trail()
     * @return array<string, mixed>
     */
    public static function present(Product $product, array $trail): array
    {
        $usedValueIds = $product->variants->flatMap(fn (ProductVariant $variant) => $variant->optionValues->modelKeys())->unique();

        return [
            'id' => $product->id,
            'type' => $product->type->value,
            'title' => $product->title,
            'slug' => $product->slug,
            'description' => $product->description,
            'image' => ProductCardPresenter::mainImage($product),
            'gallery' => $product->mediaIn('gallery')->map(fn (Media $media) => MediaPresenter::present($media, $product->title))->values()->all(),
            'brand' => $product->brand ? ['name' => $product->brand->name, 'slug' => $product->brand->slug] : null,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'title' => $product->category->title,
                'slug' => $product->category->slug,
            ] : null,
            'breadcrumbs' => array_map(fn (Category $category) => ['title' => $category->title, 'slug' => $category->slug], $trail),
            'attributes' => $product->category
                ? $product->getProductAttributesWithValues()
                : [],
            'options' => $product->options->map(fn (Option $option) => [
                'id' => $option->id,
                'name' => $option->name,
                'values' => $option->values
                    ->filter(fn (OptionValue $value) => $usedValueIds->contains($value->id))
                    ->map(fn (OptionValue $value) => ['id' => $value->id, 'value' => $value->value])
                    ->values(),
            ])->values(),
            'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'option_value_ids' => $variant->optionValues->modelKeys(),
                'price' => MoneyPresenter::present($variant->price),
                'sale_price' => $variant->isOnSale() ? MoneyPresenter::present($variant->sale_price) : null,
                'stock' => $variant->available(),
                'can_backorder' => $variant->allow_backorder,
            ])->values(),
            'default_variant_id' => $product->defaultVariant()?->id,
        ];
    }

    /**
     * Upsells first, then related products; only purchasable ones.
     *
     * @return list<array<string, mixed>>
     */
    public static function related(Product $product, int $limit = 8): array
    {
        $load = fn ($query) => $query->active()->with(ProductCardPresenter::RELATIONS);

        $product->load(['upsellProducts' => $load, 'relatedProducts' => $load]);

        return array_values($product->upsellProducts
            ->concat($product->relatedProducts)
            ->unique('id')
            ->take($limit)
            ->map(fn (Product $related) => ProductCardPresenter::present($related))
            ->all());
    }
}
