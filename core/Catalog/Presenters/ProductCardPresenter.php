<?php

namespace PnShop\Catalog\Presenters;

use PnShop\Catalog\Models\Product;
use PnShop\Money\MoneyPresenter;

/**
 * The product shape used by listings (home, shop, related products).
 * Expects `category` to be eager-loaded.
 */
final class ProductCardPresenter
{
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
            'image' => $product->image,
            'stock' => $product->stock,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'title' => $product->category->title,
                'slug' => $product->category->slug,
            ] : null,
        ];
    }
}
