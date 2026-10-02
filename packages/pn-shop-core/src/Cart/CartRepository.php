<?php

namespace PnShop\Cart;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PnShop\Cart\Models\Cart;
use PnShop\Cart\Models\CartLine;
use PnShop\Customer\Models\User;

/**
 * Finds and changes the current visitor's cart: the customer's cart when signed in,
 * otherwise the guest cart whose token is kept in the session and in a long-lived
 * cookie (so the cart outlives the session). A cart row is only created on the first
 * change, so browsing never writes to the database.
 *
 * Store API requests are stateless: the client sends the guest token in the X-Cart-Token
 * header (see useStatelessToken()), and a new cart's token is returned in the response.
 */
final class CartRepository
{
    public const COOKIE = 'pnshop_cart';

    public const COOKIE_DAYS = 30;

    /** Session key of the cart format used before carts moved to the database. */
    private const SESSION_CART = 'cart.variants';

    private const SESSION_TOKEN = 'cart.token';

    private const LINES_ATTRIBUTE = 'pnshop.cart.lines';

    private const COUPON_ATTRIBUTE = 'pnshop.cart.coupon';

    /** Request attribute holding the guest token of a stateless (API) request. */
    private const STATELESS_TOKEN = 'pnshop.cart.stateless_token';

    /**
     * @return array<int, int> variant id => quantity
     */
    public function lines(): array
    {
        $this->importSessionCart();

        // Read once per request; changes below forget the cached copy.
        $attributes = $this->request()->attributes;

        if (! $attributes->has(self::LINES_ATTRIBUTE)) {
            $cart = $this->current();
            $attributes->set(self::LINES_ATTRIBUTE, $cart === null ? [] : $cart->lines()->pluck('quantity', 'product_variant_id')->map(fn ($quantity) => (int) $quantity)->all());
        }

        return $attributes->get(self::LINES_ATTRIBUTE);
    }

    public function setQuantity(int $variantId, int $quantity): void
    {
        $cart = $this->current(create: true);

        if ($quantity <= 0) {
            $cart->lines()->where('product_variant_id', $variantId)->delete();
        } else {
            CartLine::query()->updateOrCreate(['cart_id' => $cart->id, 'product_variant_id' => $variantId], ['quantity' => $quantity]);
        }

        $cart->touch();
        $this->forgetLines();
    }

    public function clear(): void
    {
        $cart = $this->current();
        $cart?->lines()->delete();
        $cart?->update(['coupon_code' => null]);
        $this->forgetLines();
    }

    /**
     * The cart's lines read under a row lock (call inside a transaction). A second checkout
     * of the same cart waits here and then sees the lines the first one removed.
     *
     * @return array<int, int> variant id => quantity
     */
    public function lockedLines(): array
    {
        $cart = $this->current();

        if ($cart === null || Cart::query()->whereKey($cart->id)->lockForUpdate()->first() === null) {
            return [];
        }

        $this->forgetLines();

        return CartLine::query()->where('cart_id', $cart->id)->pluck('quantity', 'product_variant_id')
            ->map(fn (mixed $quantity) => (int) $quantity)
            ->all();
    }

    /** The coupon code entered for the current cart, if any. */
    public function couponCode(): ?string
    {
        $attributes = $this->request()->attributes;

        if (! $attributes->has(self::COUPON_ATTRIBUTE)) {
            $attributes->set(self::COUPON_ATTRIBUTE, $this->current()?->coupon_code);
        }

        return $attributes->get(self::COUPON_ATTRIBUTE);
    }

    public function setCouponCode(?string $code): void
    {
        $this->current(create: $code !== null)?->update(['coupon_code' => $code]);
        $this->request()->attributes->remove(self::COUPON_ATTRIBUTE);
    }

    /**
     * Move a guest cart into the customer's cart after sign-in; quantities add up.
     */
    public function mergeGuestCart(User $user, ?string $guestToken): void
    {
        if ($guestToken === null) {
            return;
        }

        $guest = Cart::query()->where('token', $guestToken)->whereNull('user_id')->first();

        if ($guest === null) {
            return;
        }

        DB::transaction(function () use ($user, $guest) {
            $cart = Cart::query()->firstOrCreate(['user_id' => $user->id], ['token' => (string) Str::uuid()]);

            foreach ($guest->lines as $line) {
                $existing = $cart->lines()->where('product_variant_id', $line->product_variant_id)->first();

                if ($existing) {
                    $existing->increment('quantity', $line->quantity);
                } else {
                    $cart->lines()->create(['product_variant_id' => $line->product_variant_id, 'quantity' => $line->quantity]);
                }
            }

            // A coupon entered as a guest carries over.
            if ($guest->coupon_code !== null) {
                $cart->update(['coupon_code' => $guest->coupon_code]);
            }

            $guest->delete();
            $cart->touch();
        });

        Cookie::queue(Cookie::forget(self::COOKIE));
        $this->request()->cookies->remove(self::COOKIE);

        if ($this->request()->hasSession()) {
            $this->request()->session()->forget(self::SESSION_TOKEN);
        }

        if ($this->request()->attributes->has(self::STATELESS_TOKEN)) {
            $this->request()->attributes->set(self::STATELESS_TOKEN, null);
        }

        $this->forgetLines();
    }

    public function current(bool $create = false): ?Cart
    {
        $user = Auth::guard('web')->user();

        if ($user instanceof User) {
            return $create
                ? Cart::query()->firstOrCreate(['user_id' => $user->id], ['token' => (string) Str::uuid()])
                : Cart::query()->where('user_id', $user->id)->first();
        }

        $token = $this->guestToken();
        $cart = $token !== null ? Cart::query()->where('token', $token)->whereNull('user_id')->first() : null;

        if ($cart === null && $create) {
            $cart = Cart::query()->create(['token' => (string) Str::uuid()]);
            $this->rememberGuestToken($cart->token);
        }

        return $cart;
    }

    /**
     * Use the given guest token for this request instead of the session and cookie, and keep
     * a newly created cart's token on the request (read it back with guestToken()).
     */
    public function useStatelessToken(?string $token): void
    {
        $this->request()->attributes->set(self::STATELESS_TOKEN, is_string($token) && Str::isUuid($token) ? $token : null);
        $this->forgetLines();
    }

    public function guestToken(): ?string
    {
        $request = $this->request();

        if ($request->attributes->has(self::STATELESS_TOKEN)) {
            return $request->attributes->get(self::STATELESS_TOKEN);
        }

        foreach ([$request->hasSession() ? $request->session()->get(self::SESSION_TOKEN) : null, $request->cookie(self::COOKIE)] as $token) {
            if (is_string($token) && Str::isUuid($token)) {
                return $token;
            }
        }

        return null;
    }

    private function rememberGuestToken(string $token): void
    {
        $request = $this->request();

        if ($request->attributes->has(self::STATELESS_TOKEN)) {
            $request->attributes->set(self::STATELESS_TOKEN, $token);

            return;
        }

        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_TOKEN, $token);
        }

        Cookie::queue(Cookie::make(self::COOKIE, $token, self::COOKIE_DAYS * 24 * 60, httpOnly: true, sameSite: 'lax'));
    }

    /**
     * Carts kept in the session before the database cart are moved over once.
     */
    private function importSessionCart(): void
    {
        $request = $this->request();

        if (! $request->hasSession() || ! $request->session()->has(self::SESSION_CART)) {
            return;
        }

        $lines = (array) $request->session()->pull(self::SESSION_CART);

        foreach ($lines as $variantId => $quantity) {
            $this->setQuantity((int) $variantId, (int) $quantity);
        }
    }

    private function forgetLines(): void
    {
        $this->request()->attributes->remove(self::LINES_ATTRIBUTE);
        $this->request()->attributes->remove(self::COUPON_ATTRIBUTE);
    }

    private function request(): Request
    {
        return app('request');
    }
}
