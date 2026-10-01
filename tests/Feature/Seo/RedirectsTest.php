<?php

namespace Tests\Feature\Seo;

use Livewire\Livewire;
use PnShop\Catalog\Models\Product;
use PnShop\Cms\Models\Page;
use PnShop\Seo\Filament\Resources\Redirects\Pages\ManageRedirects;
use PnShop\Seo\Models\Redirect;
use PnShop\Seo\Redirects;
use Tests\Feature\Admin\AdminTestCase;

class RedirectsTest extends AdminTestCase
{
    public function test_a_renamed_product_keeps_its_old_address(): void
    {
        $product = Product::factory()->active()->create(['title' => 'Desk lamp', 'slug' => 'desk-lamp']);

        $product->update(['title' => 'Reading lamp', 'slug' => 'reading-lamp']);

        $this->get('/shop/desk-lamp?ref=mail')->assertStatus(301)->assertRedirect('http://localhost/shop/reading-lamp?ref=mail');
        $this->get('/shop/reading-lamp')->assertOk();
        $this->assertSame(1, Redirect::query()->sole()->hits);
    }

    public function test_translated_slugs_redirect_within_their_language(): void
    {
        $page = Page::factory()->published()->create(['title' => 'Delivery']);
        $page->setTranslations('bg', ['slug' => 'dostavka']);
        $page->setTranslations('bg', ['slug' => 'dostavka-i-vrashtane']);

        $this->get('/bg/dostavka')->assertRedirect('http://localhost/bg/dostavka-i-vrashtane');
    }

    public function test_redirect_chains_collapse_and_reused_addresses_win(): void
    {
        $product = Product::factory()->active()->create(['slug' => 'one']);
        $product->update(['slug' => 'two']);
        $product->update(['slug' => 'three']);

        // one → three directly, two → three.
        $this->assertSame('/shop/three', Redirect::query()->where('from_path', '/shop/one')->value('to_url'));
        $this->get('/shop/one')->assertRedirect('http://localhost/shop/three');

        // Going back to an old slug makes it live again: no loop.
        $product->update(['slug' => 'one']);
        $this->assertNull(Redirect::query()->where('from_path', '/shop/one')->first());
        $this->get('/shop/one')->assertOk();
        $this->get('/shop/three')->assertRedirect('http://localhost/shop/one');
    }

    public function test_live_pages_always_win_over_redirects(): void
    {
        Redirects::add('/cart', '/shop');
        Redirects::add('/old-sale', 'https://partner.example/sale', status: 302);

        $this->get('/cart')->assertOk();
        $this->get('/old-sale/')->assertStatus(302)->assertRedirect('https://partner.example/sale');
        // Only GET and HEAD requests are redirected.
        $this->post('/old-sale')->assertStatus(405);
    }

    public function test_staff_manage_redirects(): void
    {
        $this->actingAsAdministrator();

        Livewire::test(ManageRedirects::class)
            ->callAction('create', ['from_path' => '/summer/', 'to_url' => '/shop', 'status' => 301])
            ->assertHasNoActionErrors();
        Livewire::test(ManageRedirects::class)
            ->callAction('create', ['from_path' => '/loop', 'to_url' => '/loop/'])
            ->assertHasActionErrors(['to_url']);
        Livewire::test(ManageRedirects::class)
            ->callAction('create', ['from_path' => '/x', 'to_url' => 'javascript:alert(1)'])
            ->assertHasActionErrors(['to_url']);

        $this->assertSame('/summer', Redirect::query()->sole()->from_path);
        $this->get('/summer')->assertRedirect('http://localhost/shop');
    }
}
