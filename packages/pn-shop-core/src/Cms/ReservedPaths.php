<?php

namespace PnShop\Cms;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use PnShop\Localization\Localization;

/**
 * First URL segments that belong to the shop itself (shop, cart, admin, …) or to a
 * language prefix, so a CMS page slug can never hide them.
 */
final class ReservedPaths
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $segments = collect(Router::getRoutes()->getRoutes())
            ->map(fn (Route $route) => explode('/', ltrim($route->uri(), '/'))[0])
            ->filter(fn (string $segment) => $segment !== '' && ! str_starts_with($segment, '{'));

        return array_values($segments
            ->merge(app(Localization::class)->languages()->pluck('code'))
            ->merge(['admin', 'livewire', 'storage', 'build', 'filament', 'sitemap.xml', 'robots.txt', 'favicon.ico'])
            ->map(fn (string $segment) => strtolower($segment))
            ->unique()
            ->all());
    }

    public static function isReserved(string $slug): bool
    {
        return in_array(strtolower($slug), self::all(), true);
    }
}
