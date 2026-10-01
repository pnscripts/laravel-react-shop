<?php

namespace PnShop\Catalog\Presenters;

use PnShop\Catalog\Models\Product;
use PnShop\Media\MediaPresenter;
use PnShop\Money\MoneyPresenter;

/**
 * The product shape used by listings (home, shop, related products).
 * Eager-load with ProductCardPresenter::RELATIONS to avoid N+1 queries.
 */
final class ProductCardPresenter
{
    /** @var array<int|string, mixed> */
    public const RELATIONS = ['category:id,title,slug', 'media'];

    /**
     * @return array<string, mixed>
     */
    public static function present(Product $product): array
    {
        return [
            'id' => $product->id,
            'title' => $product->title,
            'slug' => $product->slug,
            'price' => MoneyPresenter::present($product->price),
            'discount_price' => MoneyPresenter::present($product->discount_price),
            'image' => self::mainImage($product),
            'stock' => $product->stock,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'title' => $product->category->title,
                'slug' => $product->category->slug,
            ] : null,
        ];
    }

    /**
     * The first gallery image, or the legacy external image URL.
     *
     * @return array{id: int|null, url: string, thumb: string, srcset: string, alt: string, width: int|null, height: int|null}|null
     */
    public static function mainImage(Product $product): ?array
    {
        $media = $product->firstMediaIn('gallery');

        if ($media !== null) {
            return MediaPresenter::present($media, $product->title);
        }

        return $product->image ? ['id' => null, 'url' => $product->image, 'thumb' => $product->image, 'srcset' => '', 'alt' => $product->title, 'width' => null, 'height' => null] : null;
    }
}
