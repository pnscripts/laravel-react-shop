<?php

namespace PnShop\Api\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use PnShop\Cart\Exceptions\CartException;
use PnShop\Sales\Exceptions\CheckoutException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Errors of the Store and Admin APIs as RFC 9457 problem details (application/problem+json):
 * {type, title, status, detail, code} plus "errors" (field => messages) for validation.
 * "code" is a stable, machine-readable name clients can branch on.
 */
final class Problem
{
    public const CONTENT_TYPE = 'application/problem+json';

    /**
     * @param  array<string, mixed>  $extra
     * @param  array<string, string>  $headers
     */
    public static function response(int $status, string $code, ?string $detail = null, array $extra = [], array $headers = []): JsonResponse
    {
        return new JsonResponse(array_filter([
            'type' => 'about:blank',
            'title' => Response::$statusTexts[$status] ?? 'Error',
            'status' => $status,
            'detail' => $detail,
            'code' => $code,
            ...$extra,
        ], fn (mixed $value) => $value !== null), $status, [...$headers, 'Content-Type' => self::CONTENT_TYPE]);
    }

    public static function fromException(Throwable $e): JsonResponse
    {
        // The handler turns these into HTTP exceptions first; their messages name model classes.
        if ($e->getPrevious() instanceof ModelNotFoundException || $e->getPrevious() instanceof AuthorizationException) {
            $e = $e->getPrevious();
        }

        return match (true) {
            $e instanceof ValidationException => self::response(422, 'validation_failed', $e->getMessage(), ['errors' => $e->errors()]),
            $e instanceof CartException => self::response(422, 'cart_rejected', $e->getMessage()),
            $e instanceof CheckoutException => self::response(422, 'checkout_rejected', $e->getMessage()),
            $e instanceof AuthenticationException => self::response(401, 'unauthenticated', __('A valid API token is required.'), headers: ['WWW-Authenticate' => 'Bearer']),
            $e instanceof AuthorizationException => self::response(403, 'forbidden', __('This action is not allowed.')),
            $e instanceof ModelNotFoundException => self::response(404, 'not_found', __('Not found.')),
            $e instanceof HttpExceptionInterface => self::response(
                $e->getStatusCode(),
                self::codeFor($e->getStatusCode()),
                // Messages of HTTP exceptions are written for clients (abort(404, '...')); framework ones may be empty.
                $e->getMessage() !== '' ? $e->getMessage() : null,
                headers: array_map(fn (mixed $value) => (string) (is_array($value) ? implode(', ', $value) : $value), $e->getHeaders()),
            ),
            default => self::response(500, 'server_error', config('app.debug') ? $e->getMessage() : __('Something went wrong on our side.')),
        };
    }

    private static function codeFor(int $status): string
    {
        return match ($status) {
            400 => 'bad_request',
            401 => 'unauthenticated',
            403 => 'forbidden',
            404 => 'not_found',
            405 => 'method_not_allowed',
            409 => 'conflict',
            415 => 'unsupported_media_type',
            419 => 'session_expired',
            422 => 'unprocessable',
            429 => 'too_many_requests',
            503 => 'unavailable',
            default => $status >= 500 ? 'server_error' : 'error',
        };
    }
}
