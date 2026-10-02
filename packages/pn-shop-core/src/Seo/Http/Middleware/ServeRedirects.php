<?php

namespace PnShop\Seo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PnShop\Seo\Models\Redirect;
use Symfony\Component\HttpFoundation\Response;

/**
 * When a page is not found, follow a redirect for its address if there is one. Live
 * pages always win, so a redirect can never hide them.
 */
class ServeRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() !== 404 || ! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $response;
        }

        $redirect = Redirect::query()->where('from_path', Redirect::normalize($this->path($request)))->first();

        if ($redirect === null) {
            return $response;
        }

        Redirect::query()->whereKey($redirect->id)->update(['hits' => $redirect->hits + 1, 'last_hit_at' => now()]);

        $target = str_starts_with($redirect->to_url, '/') ? $this->origin($request).$redirect->to_url : $redirect->to_url;
        $query = $request->getQueryString();

        if ($query !== null && ! str_contains($target, '?')) {
            $target .= '?'.$query;
        }

        return redirect()->to($target, $redirect->status === 302 ? 302 : 301);
    }

    /** The path from the shop root, including a language prefix (/bg/old). */
    private function path(Request $request): string
    {
        return (string) $request->attributes->get('locale_prefix', '').$request->getPathInfo();
    }

    private function origin(Request $request): string
    {
        $prefix = (string) $request->attributes->get('locale_prefix', '');

        return $request->getSchemeAndHttpHost().substr($request->getBaseUrl(), 0, strlen($request->getBaseUrl()) - strlen($prefix));
    }
}
