<?php

namespace App\Services;

use App\DTOs\ShoppingCartDTO;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Exception;

class ShoppingCartService
{
    private string $sessionKey = 'shopping_cart';

    /**
     * @var Request
     */
    private Request $request;

    /**
     * ShoppingCartService constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get the shopping cart from the session.
     *
     * @return ShoppingCartDTO
     */
    private function getCart(): ShoppingCartDTO
    {
        return $this->request->session()->get($this->sessionKey, new ShoppingCartDTO());
    }

    /**
     * Save the shopping cart to the session.
     *
     * @param ShoppingCartDTO $cart
     * @return void
     */
    private function saveCart(ShoppingCartDTO $cart): void
    {
        $this->request->session()->put($this->sessionKey, $cart);
    }

    /**
     * Add an item to the cart.
     *
     * @param int $productId
     * @param int $quantity
     * @return void
     * @throws Exception
     */
    public function addItemToCart(int $productId, int $quantity): void
    {
        $product = Product::find($productId);

        if (!$product) {
            throw new Exception("Product not found.");
        }

        if ($quantity > $product->stock) {
            throw new Exception("Cannot add more than the available stock for {$product->title}. Available: {$product->stock}.");
        }

        // Get the current shopping cart
        $cart = $this->getCart();

        // Add the item to the cart
        $cart->addItem($product, $quantity);

        // Save the updated cart to session
        $this->saveCart($cart);
    }

    /**
     * Update an item's quantity in the cart.
     *
     * @param int $productId
     * @param int $quantity
     * @return void
     * @throws Exception
     */
    public function updateItemQuantityInCart(int $productId, int $quantity): void
    {
        $product = Product::find($productId);

        if (!$product) {
            throw new Exception("Product not found.");
        }

        if ($quantity > $product->stock) {
            throw new Exception("Cannot update quantity to {$quantity}. Available stock: {$product->stock}.");
        }

        // Get the current shopping cart
        $cart = $this->getCart();

        // Update the item quantity
        $cart->updateItemQuantity($product, $quantity);

        // Save the updated cart to session
        $this->saveCart($cart);
    }

    /**
     * Remove an item from the cart.
     *
     * @param int $productId
     * @return void
     */
    public function removeItemFromCart(int $productId): void
    {
        // Get the current shopping cart
        $cart = $this->getCart();

        // Remove the item from the cart
        $cart->removeItem($productId);

        // Save the updated cart to session
        $this->saveCart($cart);
    }

    /**
     * Get all items in the cart.
     *
     * @return Collection
     */
    public function getCartItems(): Collection
    {
        return $this->getCart()->getItems();
    }

    /**
     * Get the total price of the cart.
     *
     * @return float
     */
    public function getTotalPrice(): float
    {
        return $this->getCart()->getTotalPrice();
    }

    /**
     * Get the final price after applying any discounts.
     *
     * @return float
     */
    public function getFinalPrice(): float
    {
        return $this->getCart()->getFinalPrice();
    }

    /**
     * Get the total quantity of all items in the cart.
     *
     * @return int
     */
    public function getTotalQuantity(): int
    {
        return $this->getCart()->getTotalQuantity();
    }

    /**
     * Remove every item from the cart.
     */
    public function clearCart(): void
    {
        $this->request->session()->forget($this->sessionKey);
    }

    /**
     * Cart payload for Inertia pages.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'items' => $this->getCartItems()->map(fn ($item) => [
                'product_id' => $item->product_id,
                'title' => $item->title,
                'price' => $item->price,
                'discount_price' => $item->discount_price,
                'image' => $item->image,
                'stock' => $item->stock,
                'quantity' => $item->quantity,
                'line_total' => $item->getTotalPrice(),
            ])->values()->all(),
            'total_quantity' => $this->getTotalQuantity(),
            'total_price' => $this->getTotalPrice(),
            'final_price' => $this->getFinalPrice(),
        ];
    }
}

