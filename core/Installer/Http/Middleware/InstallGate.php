<?php

namespace PnShop\Installer\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use PnShop\Installer\Installation;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global middleware, first in line. Until PN Shop is installed every page leads to the web
 * installer; once it is, the installer's pages no longer exist.
 *
 * Installer requests use file sessions and a file-based rate limiter, because the database
 * may not exist yet.
 */
class InstallGate
{
    public const ATTRIBUTE = 'pnshop.installing';

    public function __construct(private Installation $installation) {}

    public function handle(Request $request, Closure $next): Response
    {
        $installerPage = $request->is('install', 'install/*');

        if (! $installerPage && ! config('pnshop.installer.enforce', true)) {
            return $next($request);
        }

        $state = $this->installation->state();

        // Never guess: an unreachable database on a live shop must not open the installer.
        if ($state === Installation::UNKNOWN) {
            return $request->expectsJson() && ! $installerPage
                ? response()->json(['message' => 'The database is not reachable.'], 503)
                : response(view('pnshop-installer::unavailable'), 503);
        }

        $installed = $state === Installation::INSTALLED;

        if ($installerPage) {
            abort_if($installed, 404);

            $request->attributes->set(self::ATTRIBUTE, true);
            // Sessions and the rate limiter must not need the database. The limiter was built at
            // boot with the default (possibly database) store; the installer's own throttles
            // need no named limiters, so a file-based one replaces it.
            $store = (string) config('pnshop.installer.cache_store', 'file');
            config(['session.driver' => 'file', 'cache.limiter' => $store]);
            app()->instance(RateLimiter::class, new RateLimiter(app('cache')->store($store)));
            Facade::clearResolvedInstance(RateLimiter::class);

            return $next($request);
        }

        if ($installed || $request->is('up')) {
            return $next($request);
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'PN Shop is not installed yet.'], 503)
            : redirect()->to(url('/install'));
    }
}
