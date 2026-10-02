<?php

namespace PnShop\Storefront;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Password;
use PnShop\Foundation\ModuleServiceProvider;
use PnShop\Foundation\PnShop;
use PnShop\Security\Http\Middleware\TrustAppHost;
use PnShop\Storefront\Http\Middleware\HandleAppearance;
use PnShop\Storefront\Http\Middleware\HandleInertiaRequests;

/**
 * The default storefront: its routes (shop, cart, checkout, account, CMS pages), the
 * Inertia middleware, rate limits and password rules. The shop's own routes/web.php is
 * loaded after these, so merchants can add routes; the CMS page fallback stays last.
 */
class StorefrontServiceProvider extends ModuleServiceProvider
{
    protected function bootModule(): void
    {
        $this->middleware();
        $this->rateLimits();

        // The storefront's pages live in the package (the shop's resources/js/pages may add more).
        config(['inertia.pages.paths' => array_values(array_unique([...(array) config('inertia.pages.paths', []), PnShop::path('resources/js/pages')]))]);

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->uncompromised()
            : Password::min(8));

        if (! $this->app->routesAreCached()) {
            Route::middleware('web')->group(PnShop::path('routes/web.php'));
        }
    }

    private function middleware(): void
    {
        EncryptCookies::except(['appearance', 'sidebar_state']);

        // Requests for other hosts are refused once installed (forged Host headers).
        $this->app->make(HttpKernel::class)->prependMiddleware(TrustAppHost::class);

        $router = $this->app->make(Router::class);

        foreach ([HandleAppearance::class, HandleInertiaRequests::class, AddLinkHeadersForPreloadedAssets::class] as $middleware) {
            $router->pushMiddlewareToGroup('web', $middleware);
        }
    }

    private function rateLimits(): void
    {
        RateLimiter::for('cart', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        RateLimiter::for('checkout', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perHour(20)->by($request->ip()),
            Limit::perHour(10)->by('email:'.strtolower((string) $request->input('email'))),
        ]);

        RateLimiter::for('auth-forms', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));
    }
}
