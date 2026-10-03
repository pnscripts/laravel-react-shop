<?php

namespace Tests\Feature\Shop;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Product;
use PnShop\Catalog\Models\ProductAttribute;
use PnShop\Catalog\Models\ProductVariant;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Models\OrderItem;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_index_shows_active_products(): void
    {
        $active = Product::factory()->active()->create([
            'title' => 'Visible Product',
            'stock' => 5,
        ]);
        Product::factory()->inactive()->create([
            'title' => 'Hidden Product',
        ]);

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('shop/index')
                ->has('products.data', 1)
                ->where('products.data.0.title', $active->title)
            );
    }

    public function test_the_shop_searches_titles_and_sorts_by_price_and_name(): void
    {
        $lamp = Product::factory()->active()->create(['title' => 'Brass Lamp', 'price' => '40.00', 'sale_price' => null]);
        $mug = Product::factory()->active()->create(['title' => 'Clay Mug', 'price' => '12.00', 'sale_price' => null]);
        $chair = Product::factory()->active()->create(['title' => 'Arm Chair', 'price' => '90.00', 'sale_price' => '30.00']);

        $this->get(route('shop.index', ['q' => 'lamp']))->assertInertia(fn (Assert $page) => $page
            ->where('filters.q', 'lamp')
            ->has('products.data', 1)
            ->where('products.data.0.id', $lamp->id));

        // The chair's sale price (30) is what it sells for.
        $ids = fn (string $sort) => collect($this->get(route('shop.index', ['sort' => $sort]))->viewData('page')['props']['products']['data'])->pluck('id')->all();

        $this->assertSame([$mug->id, $chair->id, $lamp->id], $ids('price_asc'));
        $this->assertSame([$lamp->id, $chair->id, $mug->id], $ids('price_desc'));
        $this->assertSame([$chair->id, $lamp->id, $mug->id], $ids('name'));
        $this->assertSame($ids('newest'), $ids('no-such-order'), 'An unknown order falls back to newest.');
    }

    public function test_the_category_rows_follow_the_chosen_path_at_any_depth(): void
    {
        $home = Category::factory()->create(['title' => 'Home', 'is_active' => true]);
        $kitchen = Category::factory()->create(['title' => 'Kitchen', 'parent_id' => $home->id, 'is_active' => true]);
        $cups = Category::factory()->create(['title' => 'Cups', 'parent_id' => $kitchen->id, 'is_active' => true]);

        $this->get(route('shop.index', ['category' => $kitchen->slug]))->assertInertia(fn (Assert $page) => $page
            ->where('filters.category_path', [$home->slug, $kitchen->slug])
            ->where('categories.0.children.0.children.0.slug', $cups->slug));
    }

    public function test_draft_or_inactive_products_are_hidden_from_the_storefront(): void
    {
        $inactive = Product::factory()->inactive()->create([
            'title' => 'Draft Product',
            'stock' => 10,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('home')
                ->has('products', 0)
            );

        $this->get(route('shop.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('shop/index')
                ->has('products.data', 0)
            );

        $this->get(route('shop.show', $inactive->slug))->assertNotFound();
    }

    public function test_product_page_shows_selected_attribute_values(): void
    {
        $category = Category::factory()->create();
        $attribute = ProductAttribute::factory()->create(['key' => 'color', 'label' => 'Color']);
        $category->productAttributes()->attach($attribute);
        $blue = $attribute->values()->create(['value' => 'Blue']);
        $attribute->values()->create(['value' => 'Red']);

        $product = Product::factory()->active()->create(['product_category_id' => $category->id]);
        $product->selectedAttributeValues()->attach($blue);

        $this->get(route('shop.show', $product->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('product.attributes.0.attribute', 'Color')
                ->where('product.attributes.0.value', 'Blue')
            );
    }

    public function test_multiselect_attributes_show_every_value_whatever_the_category(): void
    {
        $material = ProductAttribute::factory()->create(['key' => 'material', 'label' => 'Material', 'type' => 'multiselect']);
        $wood = $material->values()->create(['value' => 'Wood', 'position' => 1]);
        $metal = $material->values()->create(['value' => 'Metal', 'position' => 2]);

        // The attribute is not linked to the product's category: its values still show.
        $product = Product::factory()->active()->create();
        $product->selectedAttributeValues()->attach([$metal->id, $wood->id]);

        $this->get(route('shop.show', $product->slug))->assertInertia(fn (Assert $page) => $page
            ->where('product.attributes.0.attribute', 'Material')
            ->where('product.attributes.0.value', 'Wood, Metal'));
    }

    public function test_featured_products_lead_the_home_page_and_backorders_are_orderable(): void
    {
        $older = Product::factory()->active()->create(['title' => 'Featured Older', 'is_featured' => true, 'created_at' => now()->subDays(10)]);
        Product::factory()->active()->count(8)->create();

        $preorder = Product::factory()->active()->create(['stock' => 0]);
        $preorder->defaultVariant()->update(['allow_backorder' => true, 'track_inventory' => true]);

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
            ->where('products.0.id', $older->id)
            ->has('products', 8));

        $this->get(route('shop.index'))->assertInertia(fn (Assert $page) => $page
            ->where('products.data', fn ($products) => collect($products)->firstWhere('id', $preorder->id)['backorder'] === true));
    }

    public function test_guest_can_add_a_product_to_the_cart(): void
    {
        $product = Product::factory()->active()->create([
            'title' => 'Cart Product',
            'stock' => 10,
            'price' => 25,
            'sale_price' => null,
        ]);

        $this->from(route('shop.show', $product->slug))
            ->post(route('cart.store'), [
                'product_id' => $product->id,
                'quantity' => 2,
            ])
            ->assertRedirect();

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('cart/index')
                ->has('cart.items', 1)
                ->where('cart.items.0.product_id', $product->id)
                ->where('cart.items.0.quantity', 2)
            );
    }

    public function test_checkout_creates_an_order_and_decrements_stock(): void
    {
        $product = Product::factory()->active()->create([
            'title' => 'Checkout Product',
            'stock' => 10,
            'price' => 50,
            'sale_price' => null,
        ]);
        $payment = PaymentMethod::factory()->create([
            'name' => 'Cash on Delivery',
            'gateway' => 'cash_on_delivery',
            'is_active' => true,
        ]);

        $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->post(route('checkout.store'), $this->checkoutData($payment->id, ['email' => 'jane@example.com']));

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->assertEquals(8, $product->fresh()->stock);
        $this->assertEquals(1, OrderItem::query()->count());

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('cart.items', 0)
            );
    }

    public function test_factory_discounts_are_below_the_price(): void
    {
        Product::factory()->count(50)->create();

        $this->assertSame(0, ProductVariant::query()->whereColumn('sale_price', '>=', 'price')->count());
    }
}
