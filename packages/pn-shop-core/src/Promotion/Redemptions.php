<?php

namespace PnShop\Promotion;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use PnShop\Promotion\Models\Coupon;
use PnShop\Promotion\Models\Promotion;
use PnShop\Promotion\Models\PromotionRedemption;
use PnShop\Sales\Events\OrderPlacing;
use PnShop\Sales\Events\OrderReopening;
use PnShop\Sales\Events\OrderStateChanged;
use PnShop\Sales\Exceptions\CheckoutException;
use PnShop\Sales\Exceptions\OrderException;
use PnShop\Sales\Models\Order;
use PnShop\Sales\States\OrderStatus;

/**
 * Usage of promotions and coupons. Recorded inside the checkout transaction with
 * conditional updates, so concurrent orders cannot go past a usage limit; released when
 * the order is cancelled, and taken again (within the limits) when it is reopened.
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
                // A locking read sees redemptions committed by a concurrent checkout (MySQL's
                // REPEATABLE READ snapshot would not); the promotion row lock above orders them.
                // Ids are counted here: PostgreSQL refuses FOR UPDATE with COUNT().
                $used = count(PromotionRedemption::query()
                    ->where('promotion_id', $promotion->id)
                    ->where(fn (Builder $query) => $query->where('email', $email)->when($event->customer !== null, fn (Builder $query) => $query->orWhere('user_id', $event->customer?->id)))
                    ->lockForUpdate()
                    ->pluck('id')
                    ->all());

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

                $redemption->forceFill(['released_at' => now()])->save();
            }
        });
    }

    /**
     * Reopening a cancelled order takes its uses back, within the same limits as at checkout;
     * it fails (and the order stays cancelled) when an offer has been used up since.
     *
     * @throws OrderException
     */
    public function retakeOnReopen(OrderReopening $event): void
    {
        $order = $event->order;
        $email = mb_strtolower((string) $order->email);

        $released = PromotionRedemption::query()->withoutGlobalScope('in_force')
            ->where('order_id', $order->id)
            ->whereNotNull('released_at')
            ->lockForUpdate()
            ->get();

        foreach ($released as $redemption) {
            $promotion = Promotion::query()->withTrashed()->find($redemption->promotion_id);
            $label = $promotion !== null ? $promotion->name : '#'.$redemption->promotion_id;

            if ($promotion === null || ! $this->take(Promotion::query()->withTrashed()->whereKey($promotion->id), $promotion->usage_limit)) {
                throw new OrderException(__('The order cannot be reopened: the offer ":offer" has been used up.', ['offer' => $label]));
            }

            if ($redemption->coupon_id !== null && ! $this->take(Coupon::query()->whereKey($redemption->coupon_id), Coupon::query()->whereKey($redemption->coupon_id)->value('usage_limit'))) {
                throw new OrderException(__('The order cannot be reopened: its coupon code has been used up.'));
            }

            if ($promotion->usage_limit_per_customer !== null) {
                $used = count(PromotionRedemption::query()
                    ->where('promotion_id', $promotion->id)
                    ->where(fn (Builder $query) => $query->where('email', $email)->when($order->user_id !== null, fn (Builder $query) => $query->orWhere('user_id', $order->user_id)))
                    ->lockForUpdate()
                    ->pluck('id')
                    ->all());

                if ($used >= $promotion->usage_limit_per_customer) {
                    throw new OrderException(__('The order cannot be reopened: the customer has used the offer ":offer" already.', ['offer' => $label]));
                }
            }

            $redemption->forceFill(['released_at' => null])->save();
        }
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
