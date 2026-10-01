<?php

namespace Tests\Feature\Shop;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Product;
use PnShop\Catalog\Models\ProductAttribute;
use PnShop\Catalog\Models\ProductVariant;
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
        OrderStatus::factory()->create(['name' => 'pending']);
        $payment = PaymentMethod::factory()->create([
            'name' => 'Cash on Delivery',
            'type' => 'cash_on_delivery',
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
