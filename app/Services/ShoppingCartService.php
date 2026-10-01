<?php

namespace App\Services;

use App\DTOs\CartItemDTO;
use App\Exceptions\CartException;
use Brick\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PnShop\Catalog\Models\Product;
use PnShop\Localization\Localization;
use PnShop\Money\MoneyPresenter;

/**
 * Session cart. The session holds only product ids and quantities; every read
 * resolves current prices, titles and stock from the database.
 */
class ShoppingCartService
{
    private const SESSION_KEY = 'cart.lines';

    /** Session key used by the previous object-based cart; cleared on first use. */
    private const LEGACY_SESSION_KEY = 'shopping_cart';

    /** @var Collection<int, CartItemDTO>|null */
    private ?Collection $items = null;

    public function __construct(private Request $request) {}

    public function addItemToCart(int $productId, int $quantity): void
    {
        $lines = $this->getLines();

        $this->assertQuantityAvailable($productId, ($lines[$productId] ?? 0) + $quantity);

        $lines[$productId] = ($lines[$productId] ?? 0) + $quantity;
        $this->saveLines($lines);
    }

    public function updateItemQuantityInCart(int $productId, int $quantity): void
    {
        $lines = $this->getLines();

        if (! isset($lines[$productId])) {
            throw new CartException(__('Item not found in the cart.'));
        }

        $this->assertQuantityAvailable($productId, $quantity);

        $lines[$productId] = $quantity;
        $this->saveLines($lines);
    }

    public function removeItemFromCart(int $productId): void
    {
        $lines = $this->getLines();
        unset($lines[$productId]);
        $this->saveLines($lines);
    }

    /**
     * Cart lines for products that are still active, priced from the database.
     *
     * @return Collection<int, CartItemDTO>
     */
    public function getCartItems(): Collection
    {
        if ($this->items !== null) {
            return $this->items;
        }

        $lines = $this->getLines();

        $products = Product::query()
            ->active()
            ->with('media')
            ->whereKey(array_keys($lines))
            ->get()
            ->keyBy('id');

        return $this->items = collect($lines)
            ->filter(fn (int $quantity, int $productId) => $products->has($productId))
            ->map(fn (int $quantity, int $productId) => CartItemDTO::fromProduct($products[$productId], $quantity))
            ->values();
    }

    /**
     * Raw product id => quantity lines, without touching the database.
     *
     * @return array<int, int>
     */
    public function getLines(): array
    {
        $session = $this->request->session();

        if ($session->has(self::LEGACY_SESSION_KEY)) {
            $session->forget(self::LEGACY_SESSION_KEY);
        }

        $lines = $session->get(self::SESSION_KEY, []);

        return is_array($lines) ? array_map('intval', $lines) : [];
    }

    public function getTotalPrice(): Money
    {
        return $this->getCartItems()->reduce(
            fn (Money $total, CartItemDTO $item) => $total->plus($item->getTotalPrice()),
            Money::zero(app(Localization::class)->defaultCurrency()->code),
        );
    }

    public function getFinalPrice(): Money
    {
        return $this->getTotalPrice();
    }

    public function getTotalQuantity(): int
    {
        return array_sum($this->getLines());
    }

    public function clearCart(): void
    {
        $this->request->session()->forget(self::SESSION_KEY);
        $this->items = null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'items' => $this->getCartItems()->map(fn (CartItemDTO $item) => [
                'product_id' => $item->product_id,
                'title' => $item->title,
                'price' => MoneyPresenter::present($item->price),
                'discount_price' => MoneyPresenter::present($item->discount_price),
                'unit_price' => MoneyPresenter::present($item->getUnitPrice()),
                'image' => $item->image,
                'stock' => $item->stock,
                'quantity' => $item->quantity,
                'line_total' => MoneyPresenter::present($item->getTotalPrice()),
            ])->all(),
            'total_quantity' => $this->getCartItems()->sum('quantity'),
            'total_price' => MoneyPresenter::present($this->getTotalPrice()),
            'final_price' => MoneyPresenter::present($this->getFinalPrice()),
        ];
    }

    private function assertQuantityAvailable(int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new CartException(__('Quantity must be greater than 0.'));
        }

        $product = Product::query()->active()->find($productId);

        if (! $product) {
            throw new CartException(__('This product is not available.'));
        }

        if ($quantity > $product->stock) {
            throw new CartException(__('Only :stock of :product available.', ['stock' => $product->stock, 'product' => $product->title]));
        }
    }

    /**
     * @param  array<int, int>  $lines
     */
    private function saveLines(array $lines): void
    {
        $this->request->session()->put(self::SESSION_KEY, $lines);
        $this->items = null;
    }
}
