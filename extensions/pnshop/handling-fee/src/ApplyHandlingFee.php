<?php

namespace PnShop\Plugins\HandlingFee;

use App\Models\User;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Closure;
use PnShop\Cart\Totals\CartTotals;
use PnShop\Cart\Totals\TotalLine;
use PnShop\Plugins\HandlingFee\Models\Exemption;
use PnShop\Settings\Settings;

/**
 * cart.totals stage (priority 300, "fees"): the handling fee for small orders.
 */
class ApplyHandlingFee
{
    public function __construct(private Settings $settings) {}

    public function handle(CartTotals $totals, Closure $next): mixed
    {
        $fee = Money::of((string) $this->settings->get('plugin.pnshop_handling_fee.amount'), $totals->currency(), roundingMode: RoundingMode::HalfUp);
        $threshold = Money::of((string) ($this->settings->get('plugin.pnshop_handling_fee.threshold') ?: '0'), $totals->currency(), roundingMode: RoundingMode::HalfUp);
        $user = $totals->context['user'] ?? auth('web')->user();

        $exempt = $user instanceof User && $user->customer_group_id !== null
            && Exemption::query()->where('customer_group_id', $user->customer_group_id)->exists();

        if ($fee->isPositive() && ! $exempt && $totals->items->isNotEmpty() && ($threshold->isZero() || $totals->subtotal->isLessThan($threshold))) {
            $totals->add(new TotalLine('handling_fee', __('Handling fee'), $fee));
        }

        return $next($totals);
    }
}
