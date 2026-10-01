<?php

namespace PnShop\Promotion;

use Closure;
use PnShop\Cart\Totals\CartTotals;
use PnShop\Cart\Totals\TotalLine;

/**
 * cart.totals stage (priority 250, after shipping): free shipping from promotions that
 * applied with a "free shipping" action. The first such promotion takes the whole price.
 */
class ApplyShippingPromotions
{
    public const PRIORITY = 250;

    public function handle(CartTotals $totals, Closure $next): mixed
    {
        $shipping = $totals->line('shipping');

        if ($shipping === null || ! $shipping->amount->isPositive()) {
            return $next($totals);
        }

        foreach ($totals->meta['promotions'] ?? [] as $applied) {
            if (! $applied instanceof AppliedPromotion || ! $applied->freeShipping) {
                continue;
            }

            $amount = $shipping->amount->minus($totals->discountOn('shipping'));

            if ($amount->isPositive()) {
                $totals->discount('shipping', $amount);
                $totals->add(new TotalLine('discount:'.$applied->promotionId.':shipping', __('Free shipping').' — '.$applied->label, $amount->negated()));
                $applied->amount = $applied->amount->plus($amount);
            }

            break;
        }

        return $next($totals);
    }
}
