<?php

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PnShop\Acl\Models\AdminUser;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Product;
use PnShop\Cms\Models\Page;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Sales\Models\Order;
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

    public function test_product_page_cart_checkout_and_order_do_not_grow(): void
    {
        $counts = [];

        foreach ([2, 8] as $lines) {
            $this->flushSession();
            $products = Product::factory()->active()->count($lines)->create(['stock' => 50]);
            $main = $products->first();
            $main->relatedProducts()->sync($products->skip(1)->pluck('id')->mapWithKeys(fn ($id, $position) => [$id => ['position' => $position]])->all());

            foreach ($products as $product) {
                $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);
            }

            $counts[$lines] = [
                'product' => $this->countQueries('/shop/'.$main->getRawOriginal('slug')),
                'cart' => $this->countQueries('/cart'),
                'checkout' => $this->countQueries('/checkout'),
            ];

            $this->post(route('checkout.store'), $this->checkoutData(PaymentMethod::factory()->create(['gateway' => 'cash_on_delivery'])->id))->assertSessionMissing('error');
            $counts[$lines]['order'] = $this->countQueries('/orders/'.Order::query()->latest('id')->firstOrFail()->id);
        }

        $this->assertSame($counts[2], $counts[8], 'Queries grew with the number of lines: '.json_encode($counts));

        foreach ($counts[8] as $page => $count) {
            $this->assertLessThanOrEqual(30, $count, "{$page} runs {$count} queries.");
        }
    }

    public function test_api_lists_do_not_grow(): void
    {
        $admin = AdminUser::factory()->administrator()->create();
        $token = 'Bearer '.$admin->createToken('perf', ['*'])->plainTextToken;
        $counts = [];

        foreach ([3, 12] as $total) {
            $add = $total - Product::query()->count();
            Product::factory()->active()->count($add)->create();
            Order::factory()->count($add)->create();
            Category::factory()->count($add)->create();
            Page::factory()->published()->count($add)->create();

            $counts[$total] = [
                'store products' => $this->countQueries('/api/store/v1/products?per_page=50'),
                'admin products' => $this->countQueries('/api/admin/v1/products?per_page=50', ['Authorization' => $token]),
                'admin orders' => $this->countQueries('/api/admin/v1/orders?per_page=50', ['Authorization' => $token]),
                'admin customers' => $this->countQueries('/api/admin/v1/customers?per_page=50', ['Authorization' => $token]),
                'admin categories' => $this->countQueries('/api/admin/v1/categories', ['Authorization' => $token]),
                'admin pages' => $this->countQueries('/api/admin/v1/pages?per_page=50', ['Authorization' => $token]),
            ];
        }

        foreach ($counts[12] as $list => $count) {
            $this->assertLessThanOrEqual($counts[3][$list], $count, "{$list} queries grew: ".json_encode($counts));
        }
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

    /**
     * @param  array<string, string>  $headers
     */
    private function countQueries(string $url, array $headers = []): int
    {
        $this->withHeaders($headers)->get($url)->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->withHeaders($headers)->get($url)->assertOk();
        DB::disableQueryLog();
        $this->flushHeaders();

        return count(DB::getQueryLog());
    }
}
