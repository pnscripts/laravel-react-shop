<?php

namespace PnShop\Api\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PnShop\Localization\Localization;
use Symfony\Component\HttpFoundation\Response;

/**
 * Common to the Store and Admin APIs: JSON responses (also for errors), no state carried
 * over from an earlier request in the same worker, and the response language from
 * ?locale= or Accept-Language (a store language, else the default one).
 */
class ApiRequest
{
    public function __construct(private Localization $localization) {}

    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        // Guards cache their user; an API request only ever authenticates with its own token.
        Auth::forgetGuards();

        app()->setLocale($this->locale($request));

        $response = $next($request);
        $response->headers->set('Content-Language', app()->getLocale());
        $response->headers->set('Vary', trim($response->headers->get('Vary', '').', Accept-Language, Authorization', ', '));

        return $response;
    }

    private function locale(Request $request): string
    {
        $candidates = [$request->query('locale'), ...$request->getLanguages()];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            // "bg_BG" (Symfony's format) and "bg-BG" both count as "bg".
            $language = strtolower(substr(str_replace('_', '-', $candidate), 0, 2));

            if ($this->localization->isSupported($language)) {
                return $language;
            }
        }

        return $this->localization->defaultLocale();
    }
}
