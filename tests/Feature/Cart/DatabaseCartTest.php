<?php

namespace Tests\Feature\Cart;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Cart\CartRepository;
use PnShop\Cart\Models\Cart;
use PnShop\Catalog\Models\Product;
use Tests\TestCase;

class DatabaseCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_browsing_does_not_create_a_cart(): void
    {
        $this->get(route('cart.index'))->assertOk();

        $this->assertSame(0, Cart::query()->count());
    }

    public function test_guest_cart_is_found_again_by_its_cookie(): void
    {
        $product = Product::factory()->active()->create(['stock' => 5]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2])
            ->assertCookie(CartRepository::COOKIE);

        $token = Cart::query()->sole()->token;

        // A new session (for example after the browser was closed) with only the cookie.
        $this->flushSession();
        $this->withCookie(CartRepository::COOKIE, $token)
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page->where('cart.total_quantity', 2));
    }

    public function test_a_forged_cookie_does_not_reach_a_customer_cart(): void
    {
        $user = User::factory()->create();
        $cart = Cart::query()->create(['token' => fake()->uuid(), 'user_id' => $user->id]);
        $cart->lines()->create(['product_variant_id' => Product::factory()->active()->create()->defaultVariant()->id, 'quantity' => 1]);

        $this->withCookie(CartRepository::COOKIE, $cart->token)
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page->where('cart.total_quantity', 0));
    }

    public function test_guest_cart_merges_into_the_customer_cart_on_login(): void
    {
        $shared = Product::factory()->active()->create(['stock' => 10]);
        $guestOnly = Product::factory()->active()->create(['stock' => 10]);
        $user = User::factory()->create();

        $saved = Cart::query()->create(['token' => fake()->uuid(), 'user_id' => $user->id]);
        $saved->lines()->create(['product_variant_id' => $shared->defaultVariant()->id, 'quantity' => 1]);

        $this->post(route('cart.store'), ['product_id' => $shared->id, 'quantity' => 2]);
        $this->post(route('cart.store'), ['product_id' => $guestOnly->id, 'quantity' => 1]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertRedirect();

        $this->assertSame(1, Cart::query()->count());
        $this->assertEqualsCanonicalizing(
            [$shared->defaultVariant()->id => 3, $guestOnly->defaultVariant()->id => 1],
            $saved->lines()->pluck('quantity', 'product_variant_id')->all(),
        );
    }

    public function test_customer_cart_follows_the_account(): void
    {
        $product = Product::factory()->active()->create(['stock' => 5]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);

        // Another device: a fresh session, same account.
        $this->flushSession();
        $this->actingAs($user)->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page->where('cart.total_quantity', 1));
    }

    public function test_a_cart_kept_in_the_session_is_moved_to_the_database(): void
    {
        $product = Product::factory()->active()->create(['stock' => 5]);

        $this->withSession(['cart.variants' => [$product->defaultVariant()->id => 2]])
            ->get(route('cart.index'))
            ->assertInertia(fn ($page) => $page->where('cart.total_quantity', 2));

        $this->assertFalse(session()->has('cart.variants'));
        $this->assertSame(2, Cart::query()->sole()->lines()->sole()->quantity);
    }

    public function test_abandoned_carts_are_pruned(): void
    {
        $old = Cart::query()->create(['token' => fake()->uuid()]);
        $old->forceFill(['updated_at' => now()->subDays(31)])->saveQuietly();
        $recent = Cart::query()->create(['token' => fake()->uuid()]);
        $customer = Cart::query()->create(['token' => fake()->uuid(), 'user_id' => User::factory()->create()->id]);
        $customer->forceFill(['updated_at' => now()->subDays(31)])->saveQuietly();

        $this->artisan('pnshop:carts:prune')->assertSuccessful();

        $this->assertEqualsCanonicalizing([$recent->id, $customer->id], Cart::query()->pluck('id')->all());
    }
}
