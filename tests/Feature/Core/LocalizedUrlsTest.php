<?php

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;
use PnShop\Catalog\Models\Product;
use PnShop\Localization\Http\Middleware\LocalizeRequest;
use PnShop\Localization\Localization;
use PnShop\Localization\Models\Language;
use PnShop\Settings\Settings;
use Tests\TestCase;

class LocalizedUrlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_default_language_has_no_prefix(): void
    {
        $this->get('/shop')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('localization.locale', 'en')
            ->where('localization.currency', 'USD')
        );
    }

    public function test_other_languages_are_served_under_their_prefix(): void
    {
        $this->get('/bg/shop')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('shop/index')
            ->where('localization.locale', 'bg')
            ->where('ziggy.url', 'http://localhost/bg')
        );

        $this->get('/bg')->assertOk()->assertInertia(fn (Assert $page) => $page->component('home'));
    }

    public function test_generated_links_keep_the_language_prefix(): void
    {
        Product::factory()->active()->count(13)->create();

        $this->get('/bg/shop')->assertInertia(fn (Assert $page) => $page
            ->where('products.next_page_url', 'http://localhost/bg/shop?page=2')
        );
    }

    public function test_urls_are_localized_but_assets_are_not(): void
    {
        $urls = [];

        app(LocalizeRequest::class)->handle(Request::create('/bg/shop'), function () use (&$urls) {
            $urls = [route('shop.index'), url('/cart'), asset('build/app.js'), request()->fullUrl()];

            return response('ok');
        });

        $this->assertSame([
            'http://localhost/bg/shop',
            'http://localhost/bg/cart',
            'http://localhost/build/app.js',
            'http://localhost/bg/shop',
        ], $urls);
    }

    public function test_forms_posted_in_a_language_return_to_that_language(): void
    {
        $product = Product::factory()->active()->create(['stock' => 5]);

        $this->get("/bg/shop/{$product->slug}")->assertOk();

        $this->post('/bg/cart', ['product_id' => $product->id, 'quantity' => 1])
            ->assertRedirect("http://localhost/bg/shop/{$product->slug}");
    }

    public function test_the_language_switcher_points_to_the_same_page(): void
    {
        $this->get('/bg/shop?category=lamps')->assertInertia(fn (Assert $page) => $page
            ->where('localization.languages.0.code', 'en')
            ->where('localization.languages.0.url', 'http://localhost/shop?category=lamps')
            ->where('localization.languages.1.url', 'http://localhost/bg/shop?category=lamps')
            ->where('localization.languages.1.active', true)
        );
    }

    public function test_the_default_language_prefix_redirects_to_the_canonical_url(): void
    {
        $this->get('/en/shop?page=2')->assertRedirect('/shop?page=2')->assertStatus(301);
    }

    public function test_inactive_or_unknown_languages_are_not_served(): void
    {
        Language::query()->where('code', 'bg')->update(['is_active' => false]);
        app(Localization::class)->flush();

        $this->get('/bg/shop')->assertNotFound();
        $this->get('/de/shop')->assertNotFound();
    }

    public function test_the_admin_is_not_localized(): void
    {
        $this->get('/bg/admin')->assertRedirect('/admin/login');
        $this->get('/bg/up')->assertOk();
    }

    public function test_requests_use_the_store_timezone(): void
    {
        app(Settings::class)->set('localization', ['timezone' => 'Europe/Sofia']);

        $this->get('/shop')->assertOk();

        $this->assertSame('Europe/Sofia', config('app.timezone'));
    }
}
