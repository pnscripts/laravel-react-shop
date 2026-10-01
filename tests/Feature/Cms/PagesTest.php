<?php

namespace Tests\Feature\Cms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use PnShop\Catalog\Models\Product;
use PnShop\Cms\Blocks\Types\VideoBlock;
use PnShop\Cms\Models\Page;
use PnShop\Cms\PageStatus;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_published_page_is_served_at_its_slug_with_its_blocks(): void
    {
        $page = Page::factory()->published()->create(['title' => 'About us']);
        $page->syncBlocks('body', 'en', [
            ['type' => 'rich_text', 'data' => ['content' => '<p>Hello <strong>world</strong><script>alert(1)</script><a href="javascript:alert(1)">x</a></p>']],
            ['type' => 'unknown_type', 'data' => []],
            ['type' => 'video', 'data' => ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']],
        ]);

        $this->get('/about-us')->assertOk()->assertInertia(fn (Assert $inertia) => $inertia
            ->component('cms/page')
            ->where('page.title', 'About us')
            ->has('blocks', 2)
            ->where('blocks.0.type', 'rich_text')
            ->where('blocks.0.props.html', fn (string $html) => str_contains($html, '<strong>world</strong>') && ! str_contains($html, 'script') && ! str_contains($html, 'javascript'))
            ->where('blocks.1.props.embed', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
        );
    }

    public function test_drafts_scheduled_and_expired_pages_are_not_served(): void
    {
        Page::factory()->create(['title' => 'Draft']);
        Page::factory()->create(['title' => 'Later', 'status' => PageStatus::Published, 'published_at' => now()->addDay()]);
        Page::factory()->create(['title' => 'Gone', 'status' => PageStatus::Published, 'unpublished_at' => now()->subMinute()]);

        $this->get('/draft')->assertNotFound();
        $this->get('/later')->assertNotFound();
        $this->get('/gone')->assertNotFound();

        $this->travel(2)->days();
        $this->get('/later')->assertOk();
    }

    public function test_staff_preview_unpublished_pages_with_a_signed_link(): void
    {
        $page = Page::factory()->create(['title' => 'Secret plan']);

        $this->get(route('pages.preview', $page))->assertForbidden();
        $this->get(URL::temporarySignedRoute('pages.preview', now()->addHour(), ['page' => $page->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $inertia) => $inertia->where('page.preview', true));
    }

    public function test_pages_have_translated_slugs_and_fall_back_to_default_content(): void
    {
        $page = Page::factory()->published()->create(['title' => 'Delivery']);
        $page->setTranslations('bg', ['title' => 'Доставка', 'slug' => 'dostavka']);
        $page->syncBlocks('body', 'en', [['type' => 'call_to_action', 'data' => ['heading' => 'Shop now', 'button_label' => 'Go', 'button_url' => '/shop']]]);

        $this->get('/bg/dostavka')->assertOk()->assertInertia(fn (Assert $inertia) => $inertia
            ->where('page.title', 'Доставка')
            // No Bulgarian blocks yet: the default language's blocks, with localized links.
            ->where('blocks.0.props.button.url', '/bg/shop')
        );

        $page->syncBlocks('body', 'bg', [['type' => 'call_to_action', 'data' => ['heading' => 'Пазарувайте', 'button_label' => 'Към магазина', 'button_url' => 'https://example.com/x']]]);
        $this->get('/bg/dostavka')->assertInertia(fn (Assert $inertia) => $inertia->where('blocks.0.props.heading', 'Пазарувайте')->where('blocks.0.props.button.url', 'https://example.com/x'));
    }

    public function test_the_shops_own_routes_always_win_and_unknown_paths_are_404(): void
    {
        Page::factory()->published()->create(['title' => 'Cart', 'slug' => 'cart']);

        $this->get('/cart')->assertInertia(fn (Assert $inertia) => $inertia->component('cart/index'));
        $this->get('/no-such-page')->assertNotFound();
        $this->get('/a/b')->assertNotFound();
    }

    public function test_a_page_can_be_the_homepage(): void
    {
        $this->get('/')->assertInertia(fn (Assert $inertia) => $inertia->where('blocks', null));

        $first = Page::factory()->published()->create(['is_home' => true]);
        $home = Page::factory()->published()->create(['title' => 'Welcome', 'is_home' => true]);
        Product::factory()->active()->count(2)->create();
        $home->syncBlocks('body', 'en', [
            ['type' => 'hero', 'data' => ['heading' => 'Spring sale', 'button_label' => 'Shop', 'button_url' => '/shop']],
            ['type' => 'product_grid', 'data' => ['heading' => 'New', 'source' => 'latest', 'limit' => 4]],
        ]);

        $this->assertFalse($first->fresh()->is_home);
        $this->get('/')->assertInertia(fn (Assert $inertia) => $inertia
            ->where('title', 'Welcome')
            ->where('blocks.0.props.heading', 'Spring sale')
            ->has('blocks.1.props.products', 2)
        );
    }

    public function test_video_links_become_privacy_friendly_embeds(): void
    {
        $this->assertSame('https://www.youtube-nocookie.com/embed/abcdefghijk', VideoBlock::embedUrl('https://youtu.be/abcdefghijk'));
        $this->assertSame('https://player.vimeo.com/video/123456?dnt=1', VideoBlock::embedUrl('https://vimeo.com/123456'));
        $this->assertNull(VideoBlock::embedUrl('https://evil.example/embed'));
    }
}
