<?php

namespace App\Http\Controllers;

use App\Exceptions\CartException;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartRequest;
use App\Services\ShoppingCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PnShop\Catalog\Models\Product;
use PnShop\Catalog\ProductType;

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
            $this->cart->addItemToCart($this->variantId($request), (int) $request->validated('quantity'));
        } catch (CartException $e) {
            throw ValidationException::withMessages(['quantity' => $e->getMessage()]);
        }

        return back()->with('success', __('Added to cart.'));
    }

    public function update(UpdateCartRequest $request, int $variant): RedirectResponse
    {
        try {
            $this->cart->updateItemQuantityInCart($variant, (int) $request->validated('quantity'));
        } catch (CartException $e) {
            throw ValidationException::withMessages(['quantity' => $e->getMessage()]);
        }

        return back()->with('success', __('Cart updated.'));
    }

    public function destroy(int $variant): RedirectResponse
    {
        $this->cart->removeItemFromCart($variant);

        return back()->with('success', __('Item removed from cart.'));
    }

    /**
     * The variant to add: given explicitly, or a simple product's only variant.
     */
    private function variantId(AddToCartRequest $request): int
    {
        if ($request->filled('variant_id')) {
            return (int) $request->validated('variant_id');
        }

        $product = Product::query()->with('variants')->findOrFail((int) $request->validated('product_id'));

        if ($product->type === ProductType::Variable) {
            throw ValidationException::withMessages(['variant_id' => __('Please choose an option.')]);
        }

        return $product->defaultVariant()->id ?? throw ValidationException::withMessages(['quantity' => __('This product is not available.')]);
    }
}
