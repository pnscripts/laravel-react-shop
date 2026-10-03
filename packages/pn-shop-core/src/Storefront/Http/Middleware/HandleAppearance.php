<?php

namespace PnShop\Storefront\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // The value is written into an inline script in the page, so only the known ones pass.
        $appearance = $request->cookie('appearance');

        View::share('appearance', in_array($appearance, ['light', 'dark', 'system'], true) ? $appearance : 'system');

        return $next($request);
    }
}
