<?php

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Product;
use Tests\TestCase;

/**
 * Storefront pages must not issue more queries as the catalog grows (no N+1),
 * in the default language or a translated one.
 */
class QueryCountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function pages(): array
    {
        return [
            'home, default language' => ['/'],
            'home, Bulgarian' => ['/bg'],
            'shop, default language' => ['/shop'],
            'shop, Bulgarian' => ['/bg/shop'],
        ];
    }

    #[DataProvider('pages')]
    public function test_query_count_does_not_grow_with_the_catalog(string $url): void
    {
        $this->seedCatalog(3);
        $few = $this->countQueries($url);

        $this->seedCatalog(9);
        $many = $this->countQueries($url);

        $this->assertSame($few, $many, "{$url}: {$few} queries with 3 products, {$many} with 12.");
        $this->assertLessThanOrEqual(12, $many, "{$url} runs {$many} queries.");
    }

    public function test_translated_pages_show_translated_text(): void
    {
        $category = Category::factory()->create(['title' => 'Lamps']);
        $category->setTranslations('bg', ['title' => 'Лампи']);
        $product = Product::factory()->active()->create(['title' => 'Desk Lamp', 'product_category_id' => $category->id]);
        $product->setTranslations('bg', ['title' => 'Настолна лампа', 'description' => 'Описание']);

        $this->get('/bg/shop')->assertInertia(fn ($page) => $page
            ->where('products.data.0.title', 'Настолна лампа')
            ->where('products.data.0.slug', 'nastolna-lampa')
            ->where('products.data.0.category.title', 'Лампи')
            ->where('categories.0.title', 'Лампи')
            ->missing('categories.0.translations')
        );

        $this->get('/bg/shop/nastolna-lampa')->assertOk()->assertInertia(fn ($page) => $page
            ->where('product.title', 'Настолна лампа')
            ->where('product.description', 'Описание')
        );

        $this->get('/bg/shop?category=lampi')->assertInertia(fn ($page) => $page->has('products.data', 1));
        $this->get('/bg/shop?category='.$category->getRawOriginal('slug'))->assertInertia(fn ($page) => $page->has('products.data', 1));

        $sourceSlug = $product->getRawOriginal('slug');

        $this->get("/bg/shop/{$sourceSlug}")->assertOk();
        $this->get("/shop/{$sourceSlug}")->assertOk()->assertInertia(fn ($page) => $page->where('product.title', 'Desk Lamp'));
        $this->get('/shop/nastolna-lampa')->assertNotFound();
    }

    private function seedCatalog(int $count): void
    {
        foreach (range(1, $count) as $i) {
            $category = Category::factory()->create();
            $category->setTranslations('bg', ['title' => "Категория {$category->id}"]);
            $product = Product::factory()->active()->create(['product_category_id' => $category->id]);
            $product->setTranslations('bg', ['title' => "Продукт {$product->id}"]);
        }
    }

    private function countQueries(string $url): int
    {
        $this->get($url)->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }
}
