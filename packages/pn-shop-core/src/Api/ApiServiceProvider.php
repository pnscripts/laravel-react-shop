<?php

namespace PnShop\Api;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;
use PnShop\Api\Console\ApiTokenCommand;
use PnShop\Api\Http\Middleware\ApiRequest;
use PnShop\Api\Http\Middleware\Idempotent;
use PnShop\Api\Http\Middleware\StaffToken;
use PnShop\Api\Http\Middleware\StoreCustomer;
use PnShop\Api\Http\Problem;
use PnShop\Foundation\ModuleServiceProvider;
use Throwable;

/**
 * The Store API (/api/store/v1: catalog, content, cart, checkout, customer accounts) and the
 * Admin API (/api/admin/v1: integrations, with staff tokens whose abilities are permission
 * keys). Both are stateless JSON over bearer tokens (Sanctum), with problem+json errors.
 */
class ApiServiceProvider extends ModuleServiceProvider
{
    public const STORE_PREFIX = 'api/store/v1';

    public const ADMIN_PREFIX = 'api/admin/v1';

    protected function bootModule(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('pnshop.idempotent', Idempotent::class);

        $this->rateLimits();
        $this->problemResponses();

        if (! $this->app->routesAreCached()) {
            Route::prefix(self::STORE_PREFIX)
                ->name('api.store.')
                ->middleware([ApiRequest::class, StoreCustomer::class, 'api', 'throttle:store-api'])
                ->group($this->modulePath('routes/store.php'));

            Route::prefix(self::ADMIN_PREFIX)
                ->name('api.admin.')
                // Authenticate before route model binding, so callers without a token learn nothing about records.
                ->middleware([ApiRequest::class, StaffToken::class, 'api', 'throttle:admin-api'])
                ->group($this->modulePath('routes/admin.php'));
        }

        OpenApiDocs::register();

        if ($this->app->runningInConsole()) {
            $this->commands([ApiTokenCommand::class]);
        }
    }

    private function rateLimits(): void
    {
        $caller = fn (Request $request): string => ($token = self::accessToken($request)) !== null
            ? 'token:'.$token->getKey()
            : 'ip:'.$request->ip();

        RateLimiter::for('store-api', fn (Request $request) => Limit::perMinute(config()->integer('pnshop.api.store_rate_limit', 120))->by('store|'.$caller($request)));
        RateLimiter::for('admin-api', fn (Request $request) => Limit::perMinute(config()->integer('pnshop.api.admin_rate_limit', 300))->by('admin|'.$caller($request)));
    }

    /**
     * The personal access token the request authenticated with, if any.
     */
    public static function accessToken(Request $request): ?PersonalAccessToken
    {
        $token = $request->user()?->currentAccessToken();

        return $token instanceof PersonalAccessToken ? $token : null;
    }

    private function problemResponses(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if ($handler instanceof Handler) {
                $handler->renderable(fn (Throwable $e, Request $request) => $request->is('api/*') ? Problem::fromException($e) : null);
            }
        });
    }
}
