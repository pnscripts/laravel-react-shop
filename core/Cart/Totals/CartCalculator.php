<?php

namespace PnShop\Cart\Totals;

use App\DTOs\CartItemDTO;
use Brick\Money\Money;
use Illuminate\Support\Collection;
use PnShop\Foundation\Extension\PipelineRegistry;

/**
 * Computes a cart's totals: the subtotal of its lines, then every stage of the
 * "cart.totals" pipeline (shipping, discounts, tax, ...) in priority order.
 *
 * Stages are registered with PipelineRegistry::stage('cart.totals', ...) and receive
 * the CartTotals and $next, like middleware. Suggested priorities: discounts 100,
 * shipping 200, fees 300, tax 400.
 */
final class CartCalculator
{
    public const PIPELINE = 'cart.totals';

    public function __construct(private PipelineRegistry $pipelines) {}

    /**
     * @param  Collection<int, CartItemDTO>  $items
     * @param  array<string, mixed>  $context
     */
    public function calculate(Collection $items, string $currency, array $context = []): CartTotals
    {
        $subtotal = $items->reduce(
            fn (Money $total, CartItemDTO $item) => $total->plus($item->getTotalPrice()),
            Money::zero($currency),
        );

        $totals = $this->pipelines->run(self::PIPELINE, new CartTotals($items, $subtotal, $context));

        return $totals instanceof CartTotals ? $totals : throw new \UnexpectedValueException('A cart.totals stage did not return the totals.');
    }
}
