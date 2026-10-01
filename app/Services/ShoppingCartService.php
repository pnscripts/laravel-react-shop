<?php

namespace App\Services;

use App\DTOs\CartItemDTO;
use App\Exceptions\CartException;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PnShop\Catalog\Models\ProductVariant;
use PnShop\Inventory\InventoryService;
use PnShop\Localization\Localization;
use PnShop\Money\MoneyPresenter;

/**
 * Session cart. The session holds only variant ids and quantities; every read
 * resolves current prices, titles and stock from the database.
 *
 * Safe to keep across requests (controllers are cached on routes, and long-lived
 * workers reuse instances): it always reads the current request's session, and its
 * memoized items are tied to the cart contents they were built from.
 */
class ShoppingCartService
{
    private const SESSION_KEY = 'cart.variants';

    /** Session keys of earlier cart formats; cleared on first use. */
    private const LEGACY_SESSION_KEYS = ['shopping_cart', 'cart.lines'];

    /** @var Collection<int, CartItemDTO>|null */
    private ?Collection $items = null;

    /** Cart contents the memoized $items were built from. */
    private ?string $itemsFor = null;

    public function __construct(private InventoryService $inventory) {}

    public function addItemToCart(int $variantId, int $quantity): void
    {
        $lines = $this->getLines();

        $this->assertQuantityAvailable($variantId, ($lines[$variantId] ?? 0) + $quantity);

        $lines[$variantId] = ($lines[$variantId] ?? 0) + $quantity;
        $this->saveLines($lines);
    }

    public function updateItemQuantityInCart(int $variantId, int $quantity): void
    {
        $lines = $this->getLines();

        if (! isset($lines[$variantId])) {
            throw new CartException(__('Item not found in the cart.'));
        }

        $this->assertQuantityAvailable($variantId, $quantity);

        $lines[$variantId] = $quantity;
        $this->saveLines($lines);
    }

    public function removeItemFromCart(int $variantId): void
    {
        $lines = $this->getLines();
        unset($lines[$variantId]);
        $this->saveLines($lines);
    }

    /**
     * Cart lines for variants that can still be bought, priced from the database.
     *
     * @return Collection<int, CartItemDTO>
     */
    public function getCartItems(): Collection
    {
        $lines = $this->getLines();
        $signature = json_encode($lines);

        if ($this->items !== null && $this->itemsFor === $signature) {
            return $this->items;
        }

        $this->itemsFor = $signature;

        $variants = $this->purchasableVariants()
            ->whereKey(array_keys($lines))
            ->with(['product.media', 'optionValues', 'stockLevels'])
            ->get()
            ->keyBy('id');

        return $this->items = collect($lines)
            ->filter(fn (int $quantity, int $variantId) => $variants->has($variantId))
            ->map(fn (int $quantity, int $variantId) => CartItemDTO::fromVariant($variants[$variantId], $quantity))
            ->values();
    }

    /**
     * Raw variant id => quantity lines, without touching the database.
     *
     * @return array<int, int>
     */
    public function getLines(): array
    {
        $session = $this->request()->session();

        foreach (self::LEGACY_SESSION_KEYS as $key) {
            if ($session->has($key)) {
                $session->forget($key);
            }
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
        $this->request()->session()->forget(self::SESSION_KEY);
        $this->items = null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'items' => $this->getCartItems()->map(fn (CartItemDTO $item) => [
                'variant_id' => $item->variant_id,
                'product_id' => $item->product_id,
                'title' => $item->title,
                'slug' => $item->slug,
                'variant_label' => $item->variant_label,
                'sku' => $item->sku,
                'price' => MoneyPresenter::present($item->price),
                'sale_price' => MoneyPresenter::present($item->sale_price),
                'unit_price' => MoneyPresenter::present($item->getUnitPrice()),
                'image' => $item->image,
                'stock' => $item->available,
                'quantity' => $item->quantity,
                'line_total' => MoneyPresenter::present($item->getTotalPrice()),
            ])->all(),
            'total_quantity' => $this->getCartItems()->sum('quantity'),
            'total_price' => MoneyPresenter::present($this->getTotalPrice()),
            'final_price' => MoneyPresenter::present($this->getFinalPrice()),
        ];
    }

    /**
     * Active variants of active products.
     *
     * @return Builder<ProductVariant>
     */
    public function purchasableVariants(): Builder
    {
        return ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', fn (Builder $product) => $product->where('is_active', true));
    }

    private function assertQuantityAvailable(int $variantId, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new CartException(__('Quantity must be greater than 0.'));
        }

        $variant = $this->purchasableVariants()->with(['product', 'stockLevels'])->find($variantId);

        if (! $variant) {
            throw new CartException(__('This product is not available.'));
        }

        if (! $this->inventory->canSell($variant, $quantity)) {
            throw new CartException(__('Only :stock of :product available.', ['stock' => (int) $variant->available(), 'product' => $variant->product->title]));
        }
    }

    /**
     * @param  array<int, int>  $lines
     */
    private function saveLines(array $lines): void
    {
        $this->request()->session()->put(self::SESSION_KEY, $lines);
        $this->items = null;
    }

    private function request(): Request
    {
        return app('request');
    }
}
