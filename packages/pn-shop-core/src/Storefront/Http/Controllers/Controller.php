<?php

namespace PnShop\Storefront\Http\Controllers;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PnShop\Customer\Models\User;

abstract class Controller
{
    /**
     * The signed-in customer, if any.
     */
    protected function customer(Request $request): ?User
    {
        $user = $request->user('web');

        return $user instanceof User ? $user : null;
    }

    /**
     * The signed-in customer on routes behind the `auth` middleware.
     */
    protected function authenticatedCustomer(Request $request): User
    {
        $user = $this->customer($request);
        abort_if($user === null, 403);

        return $user;
    }

    /**
     * Orders this browser placed or opened through a signed link.
     *
     * @return Collection<int, int>
     */
    protected static function recentOrderIds(Request $request): Collection
    {
        $ids = $request->session()->get('recent_order_ids', []);

        return collect(is_array($ids) ? array_map(intval(...), array_values($ids)) : []);
    }

    /**
     * A date in the shop's timezone and the current language, e.g. "2 October 2026" for "LL".
     */
    protected static function displayDate(?CarbonInterface $date, string $format = 'LL'): ?string
    {
        return $date === null ? null : Carbon::instance($date)
            ->setTimezone(config('app.timezone'))
            ->settings(['locale' => app()->getLocale()])
            ->isoFormat($format);
    }
}
