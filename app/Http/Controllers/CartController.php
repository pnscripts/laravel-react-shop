<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartRequest;
use App\Models\Product;
use App\Services\ShoppingCartService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private ShoppingCartService $cart) {}

    public function index(): Response
    {
        return Inertia::render('cart/index', [
            'cart' => $this->cart->toArray(),
        ]);
    }

    public function store(AddToCartRequest $request): RedirectResponse
    {
        try {
            $this->cart->addItemToCart(
                (int) $request->validated('product_id'),
                (int) $request->validated('quantity'),
            );
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'quantity' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Added to cart.');
    }

    public function update(UpdateCartRequest $request, Product $product): RedirectResponse
    {
        try {
            $this->cart->updateItemQuantityInCart(
                $product->id,
                (int) $request->validated('quantity'),
            );
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'quantity' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Cart updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->cart->removeItemFromCart($product->id);

        return back()->with('success', 'Item removed from cart.');
    }
}
