<?php

namespace App\DTOs;

use Brick\Money\Money;
use PnShop\Catalog\Models\Product;

/**
 * A cart line built from the current product row. Prices are never taken from the session.
 */
final readonly class CartItemDTO
{
    public function __construct(
        public int $product_id,
        public string $title,
        public Money $price,
        public ?Money $discount_price,
        public ?string $image,
        public int $stock,
        public int $quantity,
    ) {}

    public static function fromProduct(Product $product, int $quantity): self
    {
        return new self(
            $product->id,
            $product->title,
            $product->price,
            $product->discount_price,
            $product->image,
            $product->stock,
            $quantity,
        );
    }

    /**
     * The price of one unit: the discount price when set, otherwise the regular price.
     */
    public function getUnitPrice(): Money
    {
        return $this->discount_price !== null && $this->discount_price->isPositive() ? $this->discount_price : $this->price;
    }

    public function getTotalPrice(): Money
    {
        return $this->getUnitPrice()->multipliedBy($this->quantity);
    }
}
