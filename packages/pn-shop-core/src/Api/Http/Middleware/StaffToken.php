<?php

namespace PnShop\Api\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PnShop\Acl\Models\AdminUser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin API: a staff token of an active admin user is required. What the token may do is
 * decided by its abilities (= permission keys, see AdminUser::hasPermissionTo()).
 */
class StaffToken
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('admin-api');
        $admin = Auth::guard('admin-api')->user();

        if (! $admin instanceof AdminUser || ! $admin->is_active) {
            throw new AuthenticationException(guards: ['admin-api']);
        }

        return $next($request);
    }
}
