<?php

namespace PnShop\Promotion;

use Closure;
use PnShop\Cart\Totals\CartTotals;
use PnShop\Cart\Totals\TotalLine;
use PnShop\Promotion\Models\Coupon;
use PnShop\Promotion\Models\Promotion;
use PnShop\Promotion\Models\PromotionRedemption;

/**
 * cart.totals stage (priority 100, "discounts"): applies the running promotions in order.
 *
 * A promotion applies when it is automatic or the cart's coupon belongs to it, it is under
 * its per-customer limit, and every condition holds (an unknown condition type never
 * holds). Its actions put discounts on lines; one negative total line per promotion shows
 * the sum. A promotion marked "stop" keeps later ones from applying. Free shipping is
 * given by ApplyShippingPromotions once the shipping price is known.
 *
 * The outcome for the coupon is left in $meta['coupon'] ({code, valid, applied, message}).
 */
class ApplyPromotions
{
    public const PRIORITY = 100;

    public function __construct(private PromotionRegistry $registry) {}

    public function handle(CartTotals $totals, Closure $next): mixed
    {
        $code = $totals->context['coupon_code'] ?? null;
        $code = is_string($code) && trim($code) !== '' ? Coupon::normalize($code) : null;
        $coupon = $code === null ? null : $this->usableCoupon($code);

        $totals->meta['promotions'] = [];

        if ($code !== null) {
            $totals->meta['coupon'] = [
                'code' => $code,
                'valid' => $coupon !== null,
                'applied' => false,
                'message' => $coupon === null ? __('This coupon code is not valid.') : __('This coupon does not apply to your cart.'),
            ];
        }

        if ($totals->items->isEmpty()) {
            return $next($totals);
        }

        $context = new PromotionContext($totals);

        $promotions = Promotion::query()->running()
            ->where(fn ($query) => $query->where('requires_coupon', false)->when($coupon !== null, fn ($query) => $query->orWhere('id', $coupon?->promotion_id)))
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        foreach ($promotions as $promotion) {
            if (! $this->withinCustomerLimit($promotion, $context) || ! $this->conditionsHold($promotion, $context)) {
                continue;
            }

            $discounts = new Discounts($context);

            foreach ($promotion->actions ?? [] as $action) {
                $this->registry->findAction((string) ($action['type'] ?? ''))?->apply($context, (array) ($action['data'] ?? []), $discounts);
            }

            if (! $discounts->total()->isPositive() && ! $discounts->givesFreeShipping()) {
                continue;
            }

            $usedCoupon = $coupon !== null && $coupon->promotion_id === $promotion->id ? $coupon : null;
            $label = $promotion->displayLabel().($usedCoupon !== null ? " ({$usedCoupon->code})" : '');

            foreach ($discounts->amounts() as $lineKey => $amount) {
                $totals->discount($lineKey, $amount);
            }

            if ($discounts->total()->isPositive()) {
                $totals->add(new TotalLine('discount:'.$promotion->id, $label, $discounts->total()->negated()));
            }

            $totals->meta['promotions'][$promotion->id] = new AppliedPromotion($promotion->id, $usedCoupon?->id, $label, $discounts->total(), $discounts->givesFreeShipping());

            if ($usedCoupon !== null) {
                $totals->meta['coupon'] = ['code' => $usedCoupon->code, 'valid' => true, 'applied' => true, 'message' => null];
            }

            if ($promotion->stop_further) {
                break;
            }
        }

        return $next($totals);
    }

    /**
     * An active coupon of a running promotion, below its own usage limit.
     */
    private function usableCoupon(string $code): ?Coupon
    {
        $coupon = Coupon::query()->where('code', $code)->where('is_active', true)->first();

        if ($coupon === null || ($coupon->usage_limit !== null && $coupon->times_used >= $coupon->usage_limit)) {
            return null;
        }

        return Promotion::query()->running()->whereKey($coupon->promotion_id)->exists() ? $coupon : null;
    }

    private function conditionsHold(Promotion $promotion, PromotionContext $context): bool
    {
        foreach ($promotion->conditions ?? [] as $condition) {
            $type = $this->registry->findCondition((string) ($condition['type'] ?? ''));

            if ($type === null || ! $type->passes($context, (array) ($condition['data'] ?? []))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Customers are recognised by account or email; guests without an email yet are let
     * through (checkout checks again with the email).
     */
    private function withinCustomerLimit(Promotion $promotion, PromotionContext $context): bool
    {
        if ($promotion->usage_limit_per_customer === null) {
            return true;
        }

        $customer = $context->customer();
        $email = $context->email();

        if ($customer === null && $email === null) {
            return true;
        }

        return PromotionRedemption::query()
            ->where('promotion_id', $promotion->id)
            ->where(fn ($query) => $query
                ->when($customer !== null, fn ($query) => $query->where('user_id', $customer?->id))
                ->when($email !== null, fn ($query) => $query->orWhere('email', $email)))
            ->count() < $promotion->usage_limit_per_customer;
    }
}
