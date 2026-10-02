<?php

namespace PnShop\Cart\Totals;

use Brick\Money\Money;
use Illuminate\Support\Collection;
use PnShop\Cart\CartItemDTO;
use PnShop\Money\MoneyPresenter;

/**
 * The payload of the "cart.totals" pipeline. Stages read the items and context
 * (shipping address, chosen shipping method, coupon, ...) and add total lines.
 */
final class CartTotals
{
    /** @var list<TotalLine> */
    private array $lines = [];

    /**
     * Discounts given on each line ("item:<variant id>", "shipping"), so later stages such
     * as tax work from what the customer actually pays.
     *
     * @var array<string, Money>
     */
    private array $discounts = [];

    /**
     * Details stages leave for later steps, e.g. `tax` (a TaxResult with the tax per line).
     *
     * @var array<string, mixed>
     */
    public array $meta = [];

    /**
     * @param  Collection<int, CartItemDTO>  $items
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly Collection $items,
        public readonly Money $subtotal,
        public readonly array $context = [],
    ) {}

    public function add(TotalLine $line): self
    {
        $this->lines[] = $line;

        return $this;
    }

    /**
     * @return list<TotalLine>
     */
    public function lines(): array
    {
        return $this->lines;
    }

    /**
     * Record a discount on one line (positive amount). A stage that does this also adds the
     * matching negative TotalLine.
     */
    public function discount(string $lineKey, Money $amount): self
    {
        $this->discounts[$lineKey] = $this->discountOn($lineKey)->plus($amount);

        return $this;
    }

    /** Discount recorded on a line so far (zero when none). */
    public function discountOn(string $lineKey): Money
    {
        return $this->discounts[$lineKey] ?? Money::zero($this->currency());
    }

    /**
     * @return array<string, Money>
     */
    public function discounts(): array
    {
        return $this->discounts;
    }

    public function line(string $code): ?TotalLine
    {
        foreach ($this->lines as $line) {
            if ($line->code === $code) {
                return $line;
            }
        }

        return null;
    }

    public function currency(): string
    {
        return $this->subtotal->getCurrency()->getCurrencyCode();
    }

    /**
     * Subtotal plus every line that is not already included in the prices, never below zero.
     */
    public function total(): Money
    {
        $total = $this->subtotal;

        foreach ($this->lines as $line) {
            if (! $line->included) {
                $total = $total->plus($line->amount);
            }
        }

        return $total->isNegative() ? Money::zero($this->currency()) : $total;
    }

    /**
     * @return array{subtotal: array<string, mixed>|null, lines: list<array<string, mixed>>, total: array<string, mixed>|null}
     */
    public function toArray(): array
    {
        return [
            'subtotal' => MoneyPresenter::present($this->subtotal),
            'lines' => array_map(fn (TotalLine $line) => [
                'code' => $line->code,
                'label' => $line->label,
                'amount' => MoneyPresenter::present($line->amount),
                'included' => $line->included,
            ], $this->lines),
            'total' => MoneyPresenter::present($this->total()),
        ];
    }
}
