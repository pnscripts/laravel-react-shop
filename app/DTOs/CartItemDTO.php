<?php

namespace App\DTOs;

use App\Models\Product;

/**
 * A cart line built from the current product row. Prices are never taken from the session.
 */
final readonly class CartItemDTO
{
    public function __construct(
        public int $product_id,
        public string $title,
        public float $price,
        public ?float $discount_price,
        public ?string $image,
        public int $stock,
        public int $quantity,
    ) {}

    public static function fromProduct(Product $product, int $quantity): self
    {
        return new self(
            $product->id,
            $product->title,
            (float) $product->price,
            $product->discount_price !== null ? (float) $product->discount_price : null,
            $product->image,
            $product->stock,
            $quantity,
        );
    }

    /**
     * The price of one unit: the discount price when set, otherwise the regular price.
     */
    public function getUnitPrice(): float
    {
        return $this->discount_price ?: $this->price;
    }

    public function getTotalPrice(): float
    {
        return $this->getUnitPrice() * $this->quantity;
    }
}
