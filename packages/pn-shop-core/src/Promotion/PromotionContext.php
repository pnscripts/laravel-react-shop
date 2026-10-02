<?php

namespace PnShop\Promotion;

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PnShop\Cart\CartItemDTO;
use PnShop\Cart\Totals\CartTotals;
use PnShop\Catalog\Models\Category;
use PnShop\Customer\PostalAddress;

/**
 * What conditions and actions look at: the cart lines, the customer, the addresses, and
 * each product's categories (loaded once, with category subtrees expanded on demand).
 */
final class PromotionContext
{
    /** @var array<int, list<int>>|null product id => category ids */
    private ?array $productCategories = null;

    /** @var array<string, list<int>> */
    private array $subtrees = [];

    public function __construct(public readonly CartTotals $totals) {}

    /**
     * @return Collection<int, CartItemDTO>
     */
    public function items(): Collection
    {
        return $this->totals->items;
    }

    public function currency(): string
    {
        return $this->totals->currency();
    }

    public function customer(): ?User
    {
        $user = $this->totals->context['user'] ?? null;

        return $user instanceof User ? $user : null;
    }

    public function email(): ?string
    {
        $email = $this->totals->context['email'] ?? $this->customer()?->email;

        return is_string($email) && $email !== '' ? mb_strtolower($email) : null;
    }

    public function shippingAddress(): ?PostalAddress
    {
        $address = $this->totals->context['shipping_address'] ?? null;

        return $address instanceof PostalAddress ? $address : null;
    }

    public static function lineKey(CartItemDTO $item): string
    {
        return 'item:'.$item->variant_id;
    }

    /** A line's amount less the discounts already given on it. */
    public function remaining(CartItemDTO $item): Money
    {
        $left = $item->getTotalPrice()->minus($this->totals->discountOn(self::lineKey($item)));

        return $left->isNegative() ? Money::zero($this->currency()) : $left;
    }

    /**
     * Whether the item is one of the products, or in one of the categories (or below them).
     * With neither list given, every item matches.
     *
     * @param  array<string, mixed>  $data  product_ids, category_ids
     */
    public function inScope(CartItemDTO $item, array $data): bool
    {
        $productIds = array_map('intval', (array) ($data['product_ids'] ?? []));
        $categoryIds = array_map('intval', (array) ($data['category_ids'] ?? []));

        if ($productIds === [] && $categoryIds === []) {
            return true;
        }

        if (in_array($item->product_id, $productIds, true)) {
            return true;
        }

        return $categoryIds !== [] && array_intersect($this->categoriesOf($item->product_id), $this->subtree(array_values($categoryIds))) !== [];
    }

    /**
     * @return list<int>
     */
    private function categoriesOf(int $productId): array
    {
        if ($this->productCategories === null) {
            $this->productCategories = [];

            DB::table('category_product')
                ->whereIn('product_id', $this->items()->pluck('product_id')->unique()->all())
                ->get(['product_id', 'product_category_id'])
                ->each(function (object $row): void {
                    $this->productCategories[(int) $row->product_id][] = (int) $row->product_category_id;
                });
        }

        return $this->productCategories[$productId] ?? [];
    }

    /**
     * @param  list<int>  $categoryIds
     * @return list<int>
     */
    private function subtree(array $categoryIds): array
    {
        sort($categoryIds);
        $key = implode(',', $categoryIds);

        return $this->subtrees[$key] ??= array_values(array_unique(Category::query()->whereKey($categoryIds)->get()
            ->flatMap(fn (Category $category) => $category->subtreeIds())
            ->map(fn (mixed $id) => (int) $id)
            ->all()));
    }
}
