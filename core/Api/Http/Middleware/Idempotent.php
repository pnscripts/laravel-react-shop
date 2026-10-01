<?php

namespace PnShop\Api\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PnShop\Api\ApiServiceProvider;
use PnShop\Api\Http\Problem;
use Symfony\Component\HttpFoundation\Response;

/**
 * Safe retries for calls that must not run twice (placing an order, starting a payment).
 *
 * A client sends an Idempotency-Key header (any unique string, e.g. a UUID). The first
 * response is stored for 24 hours under that key, for this caller; a retry with the same key
 * and body gets the stored response back (with Idempotency-Replayed: true) instead of running
 * the action again. While the first call is still running, a retry gets 409. Reusing a key
 * for a different request gets 422. Without the header the call runs normally.
 */
class Idempotent
{
    public const HEADER = 'Idempotency-Key';

    private const TTL_SECONDS = 86400;

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header(self::HEADER);

        if ($key === null || $request->isMethodSafe()) {
            return $next($request);
        }

        if ($key === '' || strlen($key) > 255) {
            return Problem::response(400, 'invalid_idempotency_key', __('The Idempotency-Key header must be 1 to 255 characters.'));
        }

        $cacheKey = 'pnshop:idempotency:'.hash('sha256', $this->caller($request)."\n".$key);
        $fingerprint = hash('sha256', $request->method()."\n".$request->path()."\n".$request->getContent());

        $stored = Cache::get($cacheKey);

        if (is_array($stored)) {
            return $this->replay($stored, $fingerprint);
        }

        $lock = Cache::lock($cacheKey.':lock', 60);

        if (! $lock->get()) {
            return Problem::response(409, 'idempotency_in_progress', __('A request with this Idempotency-Key is still being processed.'));
        }

        try {
            // Another worker may have finished between the read above and the lock.
            $stored = Cache::get($cacheKey);

            if (is_array($stored)) {
                return $this->replay($stored, $fingerprint);
            }

            $response = $next($request);

            // Server errors are not stored, so the client can retry them.
            if ($response->getStatusCode() < 500) {
                Cache::put($cacheKey, [
                    'fingerprint' => $fingerprint,
                    'status' => $response->getStatusCode(),
                    'content_type' => $response->headers->get('Content-Type'),
                    'location' => $response->headers->get('Location'),
                    'body' => (string) $response->getContent(),
                ], self::TTL_SECONDS);
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    /**
     * Keys are scoped to whoever is calling, so two clients cannot read each other's responses.
     */
    private function caller(Request $request): string
    {
        $token = ApiServiceProvider::accessToken($request);

        if ($token !== null) {
            return 'token:'.$token->getKey();
        }

        return 'cart:'.($request->header(StoreCustomer::CART_HEADER) ?? '').'|ip:'.$request->ip();
    }

    /**
     * @param  array<string, mixed>  $stored
     */
    private function replay(array $stored, string $fingerprint): Response
    {
        if ($stored['fingerprint'] !== $fingerprint) {
            return Problem::response(422, 'idempotency_key_reused', __('This Idempotency-Key was already used for a different request.'));
        }

        return new Response((string) $stored['body'], (int) $stored['status'], array_filter([
            'Content-Type' => $stored['content_type'],
            'Location' => $stored['location'],
            'Idempotency-Replayed' => 'true',
        ]));
    }
}
