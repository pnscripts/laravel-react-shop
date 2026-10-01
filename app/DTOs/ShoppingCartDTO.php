<?php

namespace App\DTOs;

use App\Models\Product;
use Illuminate\Support\Collection;

class ShoppingCartDTO
{
    /**
     * The items in the shopping cart.
     */
    public Collection $items;

    public function __construct()
    {
        // Initialize the cart items as an empty collection
        $this->items = collect();
    }

    /**
     * Add an item to the shopping cart.
     *
     * @throws \Exception
     */
    public function addItem(Product $product, int $quantity): void
    {
        // Validate the quantity
        if ($quantity <= 0) {
            throw new \Exception('Quantity must be greater than 0.');
        }

        // Check if the requested quantity exceeds the available stock
        if ($quantity > $product->stock) {
            throw new \Exception("Cannot add more than the available stock for {$product->title}. Available: {$product->stock}.");
        }

        // Find if the item already exists in the cart
        $existingItem = $this->items->firstWhere('product_id', $product->id);

        if ($existingItem) {
            // If product exists, update the quantity
            $existingItem->quantity += $quantity;
        } else {
            // Create a new CartItemDTO and add to the cart
            $cartItem = new CartItemDTO(
                $product->id,
                $product->title,
                $product->price,
                $product->discount_price, // Use discount_price if available
                $product->image,
                $product->stock, // Use product's stock for cart reference
                $quantity // Quantity added to the cart
            );
            $this->items->push($cartItem);
        }
    }

    /**
     * Update the quantity of an item in the cart.
     *
     * @throws \Exception
     */
    public function updateItemQuantity(Product $product, int $quantity): void
    {
        // Validate the quantity
        if ($quantity <= 0) {
            throw new \Exception('Quantity must be greater than 0.');
        }

        // Find the item in the cart
        $item = $this->items->firstWhere('product_id', $product->id);

        if ($item) {
            // Ensure the updated quantity does not exceed stock
            if ($quantity > $product->stock) {
                throw new \Exception("Cannot update quantity to {$quantity}. Available stock: {$product->stock}.");
            }

            // Update the quantity of the existing item
            $item->quantity = $quantity;
        } else {
            throw new \Exception('Item not found in the cart.');
        }
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(int $productId): void
    {
        // Filter out the item with the given product_id
        $this->items = $this->items->filter(fn ($item) => $item->product_id !== $productId);
    }

    /**
     * Get all items in the cart.
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * Get the total price of all items in the cart.
     */
    public function getTotalPrice(): float
    {
        // Calculate the total price of all items in the cart (Price * Quantity)
        return $this->items->sum(fn ($item) => $item->getTotalPrice());
    }

    /**
     * Get the total quantity of all items in the cart.
     */
    public function getTotalQuantity(): int
    {
        // Sum up the quantity of all items in the cart
        return $this->items->sum('quantity');
    }

    /**
     * Get the final price after any applicable discounts.
     */
    public function getFinalPrice(): float
    {
        // In this case, the final price is the same as the total price
        // But you can add more complex discount logic here if needed
        return $this->getTotalPrice();
    }

    /**
     * Validate the cart items.
     *
     * @throws \Exception
     */
    public function validateCart(): void
    {
        foreach ($this->items as $item) {
            if ($item->quantity > $item->stock) {
                throw new \Exception("Item {$item->title} has more quantity than available stock.");
            }

            if ($item->quantity <= 0) {
                throw new \Exception("Item {$item->title} has an invalid quantity.");
            }
        }
    }
}
