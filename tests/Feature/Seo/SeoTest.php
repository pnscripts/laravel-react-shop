<?php

namespace Tests\Feature\Seo;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PnShop\Catalog\Models\Brand;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Product;
use PnShop\Cms\Models\Page;
use PnShop\Settings\Settings;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(Settings::class)->set('store', ['name' => 'PN Demo']);
    }

    public function test_product_pages_have_meta_alternates_and_structured_data(): void
    {
        $lamps = Category::factory()->create(['title' => 'Lamps']);
        $product = Product::factory()->active()->create([
            'title' => 'Desk lamp </script><script>alert(1)</script>',
            'description' => '<p>A warm   light for long evenings.</p>',
            'product_category_id' => $lamps->id,
            'brand_id' => Brand::query()->create(['name' => 'Lumo'])->id,
            'price' => '49.90',
            'sale_price' => null,
            'stock' => 3,
            'sku' => 'LAMP-1',
            'barcode' => '5901234123457',
        ]);
        $product->setTranslations('bg', ['title' => 'Настолна лампа', 'slug' => 'nastolna-lampa']);

        $html = $this->get('/shop/'.$product->slug)->assertOk()->getContent();

        $this->assertStringContainsString('<meta name="description" content="A warm light for long evenings.">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="http://localhost/shop/'.$product->slug.'">', $html);
        $this->assertStringContainsString('<link rel="alternate" hreflang="bg" href="http://localhost/bg/shop/nastolna-lampa">', $html);
        $this->assertStringContainsString('<meta name="robots" content="index,follow">', $html);
        $this->assertStringContainsString('<meta property="og:type" content="product">', $html);
        // The title cannot break out of the JSON-LD script.
        $this->assertStringNotContainsString('</script><script>alert(1)', $html);

        $graph = $this->jsonLd($html);
        $offer = collect($graph)->firstWhere('@type', 'Product');
        $this->assertSame('49.90', $offer['offers']['price']);
        $this->assertSame('USD', $offer['offers']['priceCurrency']);
        $this->assertSame('https://schema.org/InStock', $offer['offers']['availability']);
        $this->assertSame('5901234123457', $offer['gtin']);
        $this->assertSame('Lumo', $offer['brand']['name']);
        $this->assertSame(['Shop', 'Lamps'], array_slice(array_column(collect($graph)->firstWhere('@type', 'BreadcrumbList')['itemListElement'], 'name'), 0, 2));

        // The language switcher follows the translated slug.
        $this->get('/shop/'.$product->slug)->assertInertia(fn (Assert $page) => $page->where('localization.languages.1.url', 'http://localhost/bg/shop/nastolna-lampa'));
    }

    public function test_private_and_filtered_pages_are_not_indexed(): void
    {
        $category = Category::factory()->create(['title' => 'Lamps']);

        $this->assertStringContainsString('content="noindex,follow"', $this->get('/cart')->getContent());
        $this->assertStringContainsString('content="noindex,follow"', $this->get('/shop?page=2')->getContent());

        $listing = $this->get('/shop?category='.$category->slug)->getContent();
        $this->assertStringContainsString('content="index,follow"', $listing);
        $this->assertStringContainsString('<link rel="canonical" href="http://localhost/shop?category='.$category->slug.'">', $listing);

        $filtered = $this->get('/shop?category='.$category->slug.'&page=2')->getContent();
        $this->assertStringContainsString('content="noindex,follow"', $filtered);
    }

    public function test_cms_pages_and_the_home_page_describe_themselves(): void
    {
        Page::factory()->published()->create(['title' => 'About us', 'meta_description' => 'Who we are.']);
        app(Settings::class)->set('store', ['name' => 'PN Demo', 'email' => 'hi@example.com']);

        $about = $this->get('/about-us')->getContent();
        $this->assertStringContainsString('<title data-inertia>About us - PN Demo</title>', $about);
        $this->assertStringContainsString('content="Who we are."', $about);

        $home = $this->jsonLd($this->get('/')->getContent());
        $this->assertSame(['Organization', 'WebSite'], array_column($home, '@type'));
        $this->assertSame('hi@example.com', $home[0]['email']);
    }

    public function test_a_staging_copy_can_hide_from_search_engines(): void
    {
        app(Settings::class)->set('seo', ['allow_indexing' => false]);

        $this->assertStringContainsString('content="noindex,follow"', $this->get('/')->getContent());
        $this->get('/robots.txt')->assertOk()->assertSeeText("User-agent: *\nDisallow: /");
        $this->get('/sitemap.xml')->assertNotFound();
    }

    public function test_robots_txt_hides_private_areas_and_points_to_the_sitemap(): void
    {
        app(Settings::class)->set('seo', ['robots_extra' => 'Disallow: /campaign']);

        $robots = (string) $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->getContent();

        foreach (['Disallow: /checkout', 'Disallow: /bg/checkout', 'Disallow: /admin', 'Disallow: /campaign', 'Sitemap: http://localhost/sitemap.xml'] as $line) {
            $this->assertStringContainsString($line, $robots);
        }
        $this->assertStringNotContainsString("Disallow: /\n", $robots);
    }

    public function test_sitemaps_list_live_content_in_every_language(): void
    {
        $product = Product::factory()->active()->create(['title' => 'Mug']);
        $product->setTranslations('bg', ['title' => 'Чаша', 'slug' => 'chasha']);
        Product::factory()->create(['title' => 'Hidden', 'is_active' => false]);
        Page::factory()->published()->create(['title' => 'Delivery']);
        Page::factory()->create(['title' => 'Draft']);

        $index = (string) $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
        $this->assertStringContainsString('<loc>http://localhost/sitemaps/en-products-1.xml</loc>', $index);
        $this->assertStringContainsString('<loc>http://localhost/sitemaps/bg-pages.xml</loc>', $index);

        $products = (string) $this->get('/sitemaps/bg-products-1.xml')->assertOk()->getContent();
        $this->assertStringContainsString('<loc>http://localhost/bg/shop/chasha</loc>', $products);
        $this->assertStringContainsString('hreflang="en" href="http://localhost/shop/'.$product->slug.'"', $products);
        $this->assertStringNotContainsString('hidden', $products);

        $pages = (string) $this->get('/sitemaps/en-pages.xml')->getContent();
        $this->assertStringContainsString('<loc>http://localhost/delivery</loc>', $pages);
        $this->assertStringContainsString('<loc>http://localhost/shop</loc>', $pages);
        $this->assertStringNotContainsString('draft', $pages);
        $this->assertNotFalse(simplexml_load_string($pages));

        $this->get('/sitemaps/xx-pages.xml')->assertNotFound();
        $this->get('/sitemaps/en-nonsense.xml')->assertNotFound();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function jsonLd(string $html): array
    {
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match);
        $document = json_decode($match[1] ?? '', true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('https://schema.org', $document['@context']);

        return $document['@graph'];
    }
}
