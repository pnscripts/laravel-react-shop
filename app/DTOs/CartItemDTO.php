<?php

namespace App\DTOs;

class CartItemDTO
{
    public int $product_id;

    public string $title;

    public float $price;

    public ?float $discount_price;

    public ?string $image;

    public int $stock;

    public int $quantity;

    public function __construct(int $product_id, string $title, float $price, ?float $discount_price, ?string $image, int $stock, int $quantity)
    {
        $this->product_id = $product_id;
        $this->title = $title;
        $this->price = $price;
        $this->discount_price = $discount_price;
        $this->image = $image;
        $this->stock = $stock;
        $this->quantity = $quantity;
    }

    /**
     * Get the total price of this item (use discount_price if available, otherwise use price).
     */
    public function getTotalPrice(): float
    {
        $itemPrice = $this->discount_price ?: $this->price; // Use discount_price if available

        return $itemPrice * $this->quantity;
    }
}
