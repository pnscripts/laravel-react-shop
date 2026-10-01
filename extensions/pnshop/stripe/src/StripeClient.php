<?php

namespace PnShop\Plugins\Stripe;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PnShop\Settings\Settings;

/**
 * A minimal Stripe API client: form-encoded requests with the secret key, idempotency
 * keys for writes, and Stripe's error message on failure (never the key).
 */
class StripeClient
{
    public const API = 'https://api.stripe.com/v1/';

    public function __construct(private Settings $settings) {}

    public function configured(): bool
    {
        return filled($this->settings->get('plugin.pnshop_stripe.secret_key'));
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function post(string $path, array $params, ?string $idempotencyKey = null): array
    {
        return $this->send('post', $path, $params, $idempotencyKey);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->send('get', $path, $query, null);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function send(string $method, string $path, array $params, ?string $idempotencyKey): array
    {
        $key = (string) $this->settings->get('plugin.pnshop_stripe.secret_key');

        if ($key === '') {
            throw new StripeException(__('Stripe is not configured.'));
        }

        $request = Http::withToken($key)->asForm()->acceptJson()->timeout(20)->retry(2, 300, throw: false);

        if ($idempotencyKey !== null) {
            $request = $request->withHeaders(['Idempotency-Key' => $idempotencyKey]);
        }

        try {
            $response = $method === 'get' ? $request->get(self::API.$path, $params) : $request->post(self::API.$path, $params);
        } catch (ConnectionException) {
            throw new StripeException(__('Stripe could not be reached. Please try again.'));
        }

        $data = $response->json();

        if (! $response->successful() || ! is_array($data)) {
            $message = is_array($data) && is_string($data['error']['message'] ?? null) ? $data['error']['message'] : 'HTTP '.$response->status();

            throw new StripeException('Stripe: '.$message);
        }

        return $data;
    }
}
