<?php

namespace PnShop\Promotion;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use PnShop\Promotion\Models\Coupon;
use PnShop\Promotion\Models\Promotion;
use PnShop\Promotion\Models\PromotionRedemption;
use PnShop\Sales\Events\OrderPlacing;
use PnShop\Sales\Events\OrderStateChanged;
use PnShop\Sales\Exceptions\CheckoutException;
use PnShop\Sales\Models\Order;
use PnShop\Sales\States\OrderStatus;

/**
 * Usage of promotions and coupons. Recorded inside the checkout transaction with
 * conditional updates, so concurrent orders cannot go past a usage limit; released when
 * the order is cancelled.
 */
class Redemptions
{
    public function record(OrderPlacing $event): void
    {
        $order = $event->order;
        $email = mb_strtolower($order->email);

        foreach ($event->totals->meta['promotions'] ?? [] as $applied) {
            if (! $applied instanceof AppliedPromotion) {
                continue;
            }

            $promotion = Promotion::query()->find($applied->promotionId);

            if ($promotion === null || ! $this->take(Promotion::query()->whereKey($promotion->id), $promotion->usage_limit)) {
                throw new CheckoutException(__('The offer ":offer" is no longer available. Please review your cart.', ['offer' => $applied->label]));
            }

            if ($applied->couponId !== null && ! $this->take(Coupon::query()->whereKey($applied->couponId), Coupon::query()->whereKey($applied->couponId)->value('usage_limit'))) {
                throw new CheckoutException(__('The coupon code has been used up. Please remove it and try again.'));
            }

            // The cart could not know a guest's email: check the per-customer limit again.
            if ($promotion->usage_limit_per_customer !== null) {
                $used = PromotionRedemption::query()
                    ->where('promotion_id', $promotion->id)
                    ->where(fn (Builder $query) => $query->where('email', $email)->when($event->customer !== null, fn (Builder $query) => $query->orWhere('user_id', $event->customer?->id)))
                    ->count();

                if ($used >= $promotion->usage_limit_per_customer) {
                    throw new CheckoutException(__('You have already used the offer ":offer".', ['offer' => $applied->label]));
                }
            }

            PromotionRedemption::query()->create([
                'promotion_id' => $promotion->id,
                'coupon_id' => $applied->couponId,
                'order_id' => $order->id,
                'user_id' => $event->customer?->id,
                'email' => $email,
                'currency' => $order->currency,
                'amount' => $applied->amount,
            ]);
        }
    }

    /**
     * A cancelled order gives its uses back.
     */
    public function releaseOnCancel(OrderStateChanged $event): void
    {
        if ($event->to === OrderStatus::Cancelled) {
            $this->release($event->order);
        }
    }

    public function release(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach (PromotionRedemption::query()->where('order_id', $order->id)->lockForUpdate()->get() as $redemption) {
                Promotion::query()->withTrashed()->whereKey($redemption->promotion_id)->where('times_used', '>', 0)->decrement('times_used');

                if ($redemption->coupon_id !== null) {
                    Coupon::query()->whereKey($redemption->coupon_id)->where('times_used', '>', 0)->decrement('times_used');
                }

                $redemption->delete();
            }
        });
    }

    /**
     * Count one use, unless the limit is reached (a single conditional UPDATE).
     *
     * @template TModel of Promotion|Coupon
     *
     * @param  Builder<TModel>  $query
     */
    private function take(Builder $query, mixed $limit): bool
    {
        return $query
            ->when($limit !== null, fn (Builder $query) => $query->where('times_used', '<', (int) $limit))
            ->increment('times_used') > 0;
    }
}
