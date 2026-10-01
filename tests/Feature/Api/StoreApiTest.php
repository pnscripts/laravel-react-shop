<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use PnShop\Acl\Models\AdminUser;
use PnShop\Cart\Models\Cart;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Product;
use PnShop\Cms\Models\Page;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Sales\Models\Order;
use Tests\TestCase;

class StoreApiTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/store/v1';

    public function test_store_information(): void
    {
        $this->getJson(self::API.'/store')
            ->assertOk()
            ->assertJsonStructure(['data' => ['name', 'currency', 'locale', 'default_locale', 'languages', 'countries']]);
    }

    public function test_products_are_listed_with_cursor_pagination_and_filters(): void
    {
        $shoes = Category::factory()->create(['title' => 'Shoes', 'slug' => 'shoes', 'is_active' => true]);
        $first = Product::factory()->active()->create(['title' => 'Trail runner', 'product_category_id' => $shoes->id]);
        Product::factory()->active()->count(2)->create(['product_category_id' => Category::factory()->create(['is_active' => true])->id]);
        Product::factory()->create(['is_active' => false]);

        $page = $this->getJson(self::API.'/products?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'slug', 'price' => ['amount', 'minor', 'currency', 'formatted']]], 'links' => ['next', 'prev'], 'meta' => ['next_cursor']]);

        $this->getJson($page->json('links.next'))->assertOk()->assertJsonCount(1, 'data');

        $this->getJson(self::API.'/products?category=shoes')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $first->id);
        $this->getJson(self::API.'/products?q=trail')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson(self::API.'/products?category=missing')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_unknown_sort_is_a_problem_response(): void
    {
        $this->getJson(self::API.'/products?sort=price')
            ->assertStatus(400)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('code', 'bad_request');
    }

    public function test_product_detail_and_not_found_problem(): void
    {
        $product = Product::factory()->active()->create(['title' => 'Desk lamp', 'slug' => 'desk-lamp']);

        $this->getJson(self::API.'/products/desk-lamp')
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.default_variant_id', $product->defaultVariant()->id)
            ->assertJsonStructure(['data' => ['variants', 'options', 'gallery', 'breadcrumbs', 'related']]);

        $this->getJson(self::API.'/products/nothing-here')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 404)
            ->assertJsonPath('code', 'not_found')
            ->assertJsonMissingPath('detail.model');

        $this->assertStringNotContainsString('PnShop', (string) $this->getJson(self::API.'/products/nothing-here')->getContent());
    }

    public function test_published_pages_only(): void
    {
        Page::factory()->published()->create(['title' => 'About us', 'slug' => 'about-us']);
        Page::factory()->create(['title' => 'Draft', 'slug' => 'draft']);

        $this->getJson(self::API.'/pages')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson(self::API.'/pages/about-us')->assertOk()->assertJsonPath('data.title', 'About us')->assertJsonStructure(['data' => ['blocks']]);
        $this->getJson(self::API.'/pages/draft')->assertNotFound();
        $this->getJson(self::API.'/menus/nope')->assertNotFound();
    }

    public function test_guest_cart_lives_on_the_cart_token_header(): void
    {
        $product = Product::factory()->active()->create(['stock' => 5]);

        $response = $this->postJson(self::API.'/cart/items', ['product_id' => $product->id, 'quantity' => 2])
            ->assertCreated()
            ->assertJsonPath('data.total_quantity', 2);

        $token = $response->json('data.token');
        $this->assertIsString($token);
        $response->assertCookieMissing('pnshop_cart');

        // Without the header it is a different (empty) cart.
        $this->getJson(self::API.'/cart')->assertOk()->assertJsonPath('data.total_quantity', 0);

        $variant = $product->defaultVariant()->id;
        $this->withHeader('X-Cart-Token', $token)->patchJson(self::API."/cart/items/{$variant}", ['quantity' => 3])->assertOk()->assertJsonPath('data.total_quantity', 3);

        $this->withHeader('X-Cart-Token', $token)->patchJson(self::API."/cart/items/{$variant}", ['quantity' => 50])
            ->assertStatus(422)
            ->assertJsonPath('code', 'cart_rejected');

        $this->withHeader('X-Cart-Token', $token)->deleteJson(self::API."/cart/items/{$variant}")->assertOk()->assertJsonPath('data.total_quantity', 0);
    }

    public function test_guest_checkout_with_idempotency_key(): void
    {
        $product = Product::factory()->active()->create(['stock' => 5]);
        $method = PaymentMethod::factory()->create(['gateway' => 'cash_on_delivery']);

        $token = $this->postJson(self::API.'/cart/items', ['product_id' => $product->id, 'quantity' => 1])->json('data.token');

        $this->withHeader('X-Cart-Token', $token)->getJson(self::API.'/checkout/payment-methods')
            ->assertOk()
            ->assertJsonPath('data.0.id', $method->id);

        $body = Arr::except($this->checkoutData($method->id), ['website', 'form_started_at']);
        $headers = ['X-Cart-Token' => $token, 'Idempotency-Key' => 'order-attempt-1'];

        $first = $this->withHeaders($headers)->postJson(self::API.'/checkout', $body)
            ->assertCreated()
            ->assertJsonPath('data.email', 'jane@example.com')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('payment.outcome', 'pending');

        // A retry (e.g. after a timeout) gets the same order back instead of a second one.
        $this->withHeaders($headers)->postJson(self::API.'/checkout', $body)
            ->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertJsonPath('data.id', $first->json('data.id'));

        $this->assertSame(1, Order::query()->count());

        // The same key for a different request is refused.
        $this->withHeaders($headers)->postJson(self::API.'/checkout', [...$body, 'email' => 'other@example.com'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'idempotency_key_reused');

        // The guest reads the order through the signed link only.
        $this->getJson($first->json('links.order'))->assertOk()->assertJsonPath('data.number', $first->json('data.number'));
        $this->getJson(self::API.'/orders/'.$first->json('data.id'))->assertNotFound();
    }

    public function test_checkout_with_an_empty_cart_is_rejected(): void
    {
        $method = PaymentMethod::factory()->create(['gateway' => 'cash_on_delivery']);

        $this->postJson(self::API.'/checkout', Arr::except($this->checkoutData($method->id), ['website', 'form_started_at']))
            ->assertStatus(422)
            ->assertJsonPath('code', 'checkout_rejected');
    }

    public function test_customer_login_merges_the_guest_cart_and_reaches_the_account(): void
    {
        $user = User::factory()->create(['email' => 'ana@example.com']);
        $product = Product::factory()->active()->create(['stock' => 5]);

        $cartToken = $this->postJson(self::API.'/cart/items', ['product_id' => $product->id, 'quantity' => 2])->json('data.token');

        $token = $this->withHeader('X-Cart-Token', $cartToken)
            ->postJson(self::API.'/auth/login', ['email' => 'ana@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.customer.id', $user->id)
            ->json('data.token');

        // "<id>|<prefix><secret>": the prefix lets secret scanners recognise leaked tokens.
        $this->assertStringContainsString('|pnshop_', $token);
        $this->assertSame($user->id, Cart::query()->sole()->user_id);

        $auth = ['Authorization' => 'Bearer '.$token];
        $this->withHeaders($auth)->getJson(self::API.'/cart')->assertOk()->assertJsonPath('data.total_quantity', 2)->assertJsonPath('data.token', null);
        $this->withHeaders($auth)->getJson(self::API.'/account')->assertOk()->assertJsonPath('data.email', 'ana@example.com');

        $address = $this->withHeaders($auth)->postJson(self::API.'/account/addresses', [
            'first_name' => 'Ana', 'last_name' => 'Petrova', 'line1' => '1 Vitosha Blvd', 'city' => 'Sofia', 'postcode' => '1000', 'country_code' => 'BG',
        ])->assertCreated()->assertJsonPath('data.is_default_shipping', true)->json('data.id');

        // Another customer cannot touch it.
        $other = User::factory()->create()->createToken('x', ['store'])->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$other)->deleteJson(self::API."/account/addresses/{$address}")->assertNotFound();

        $this->withHeaders($auth)->postJson(self::API.'/auth/logout')->assertNoContent();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_wrong_password_and_bad_tokens(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->postJson(self::API.'/auth/login', ['email' => 'ana@example.com', 'password' => 'nope'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonStructure(['errors' => ['email']]);

        $this->withHeader('Authorization', 'Bearer 1|not-a-token')->getJson(self::API.'/products')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Bearer');

        $this->getJson(self::API.'/account')->assertUnauthorized()->assertJsonPath('code', 'unauthenticated');

        // A staff token is not a customer token.
        $staff = AdminUser::factory()->administrator()->create()->createToken('erp', ['*'])->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$staff)->getJson(self::API.'/account')->assertUnauthorized();
    }

    public function test_registration_returns_a_token(): void
    {
        $this->postJson(self::API.'/auth/register', ['name' => 'New Customer', 'email' => 'new@example.com', 'password' => 'a-Long-password-1'])
            ->assertCreated()
            ->assertJsonPath('data.customer.email', 'new@example.com')
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertSame(1, User::query()->sole()->tokens()->count());
    }

    public function test_customer_orders_are_private(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('app', ['store'])->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)->getJson(self::API.'/account/orders')->assertOk()->assertJsonPath('data.0.id', $order->id);
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson(self::API."/orders/{$order->id}")->assertOk();

        $stranger = User::factory()->create()->createToken('app', ['store'])->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$stranger)->getJson(self::API."/orders/{$order->id}")->assertNotFound();
    }

    public function test_response_language_follows_accept_language(): void
    {
        $this->withHeader('Accept-Language', 'bg-BG,bg;q=0.9')->getJson(self::API.'/store')
            ->assertOk()
            ->assertHeader('Content-Language', 'bg')
            ->assertJsonPath('data.locale', 'bg');

        $this->flushHeaders();
        $this->getJson(self::API.'/store?locale=xx')->assertJsonPath('data.locale', 'en');
    }
}
