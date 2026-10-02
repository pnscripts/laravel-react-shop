<?php

namespace PnShop\Seo;

use Illuminate\Support\Facades\DB;
use PnShop\Seo\Models\Redirect;

/**
 * Adds redirects without loops or chains: a new address that was redirected becomes
 * live again, and redirects to the old address now point to the new one.
 */
final class Redirects
{
    public static function add(string $from, string $to, bool $automatic = false, int $status = 301): ?Redirect
    {
        $from = Redirect::normalize($from);
        $target = str_starts_with($to, '/') ? Redirect::normalize($to) : $to;

        if ($from === $target || $from === '/') {
            return null;
        }

        return DB::transaction(function () use ($from, $target, $automatic, $status) {
            Redirect::query()->where('from_path', $target)->delete();
            Redirect::query()->where('to_url', $from)->update(['to_url' => $target]);

            return Redirect::query()->updateOrCreate(['from_path' => $from], ['to_url' => $target, 'is_automatic' => $automatic, 'status' => $status]);
        });
    }
}
