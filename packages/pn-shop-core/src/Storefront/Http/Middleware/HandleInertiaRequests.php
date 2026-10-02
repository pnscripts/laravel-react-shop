<?php

namespace PnShop\Storefront\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use PnShop\Cart\ShoppingCartService;
use PnShop\Cms\Menus;
use PnShop\Settings\Settings;
use PnShop\Theme\ThemeManager;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'pnshop::app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        // A different theme bundle is a different asset version: browsers reload fully.
        $theme = app(ThemeManager::class)->active();
        $manifest = $theme->builtin ? null : public_path($theme->buildDirectory().'/manifest.json');

        return md5(parent::version($request).'|'.$theme->id.'|'.($manifest !== null && is_file($manifest) ? md5_file($manifest) : ''));
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => fn () => app(Settings::class)->get('store.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            'cartCount' => fn () => app(ShoppingCartService::class)->getTotalQuantity(),
            'menus' => fn () => ['header' => Menus::tree('header'), 'footer' => Menus::tree('footer')],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
