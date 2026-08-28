<?php

namespace Tests\Feature\ShoppingCart;

use App\Models\Product;
use App\Services\ShoppingCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ShoppingCartServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Product $product1;

    protected Product $product2;

    protected Product $product3;

    protected Product $product4;

    protected ShoppingCartService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product1 = Product::factory()->active()->create([
            'price' => 100,
            'discount_price' => 80,
            'stock' => 10,
            'title' => 'Test Product 1',
            'image' => 'test-product-1.jpg',
        ]);

        $this->product2 = Product::factory()->active()->create([
            'price' => 200,
            'discount_price' => 150,
            'stock' => 5,
            'title' => 'Test Product 2',
            'image' => 'test-product-2.jpg',
        ]);

        $this->product3 = Product::factory()->active()->create([
            'price' => 300,
            'discount_price' => 250,
            'stock' => 3,
            'title' => 'Test Product 3',
            'image' => 'test-product-3.jpg',
        ]);

        $this->product4 = Product::factory()->active()->create([
            'price' => 400,
            'discount_price' => 350,
            'stock' => 2,
            'title' => 'Test Product 4',
            'image' => 'test-product-4.jpg',
        ]);

        $this->service = $this->makeCartService();
    }

    public function test_add_multiple_items_to_cart_with_product_1_added_twice(): void
    {
        $this->service->addItemToCart($this->product1->id, 2);
        $this->service->addItemToCart($this->product1->id, 3);
        $this->service->addItemToCart($this->product2->id, 1);
        $this->service->addItemToCart($this->product3->id, 3);
        $this->service->addItemToCart($this->product4->id, 1);

        $cartItems = $this->service->getCartItems();

        $this->assertCount(4, $cartItems);
        $this->assertEquals(5, $cartItems->where('product_id', $this->product1->id)->first()->quantity);
        $this->assertEquals(1, $cartItems->where('product_id', $this->product2->id)->first()->quantity);
        $this->assertEquals(3, $cartItems->where('product_id', $this->product3->id)->first()->quantity);
        $this->assertEquals(1, $cartItems->where('product_id', $this->product4->id)->first()->quantity);
        $this->assertEquals(80 * 5 + 150 * 1 + 250 * 3 + 350 * 1, $this->service->getTotalPrice());
    }

    public function test_update_multiple_items_in_cart(): void
    {
        $this->service->addItemToCart($this->product1->id, 2);
        $this->service->addItemToCart($this->product2->id, 1);
        $this->service->addItemToCart($this->product3->id, 3);
        $this->service->addItemToCart($this->product4->id, 1);

        $this->service->updateItemQuantityInCart($this->product1->id, 5);
        $this->service->updateItemQuantityInCart($this->product2->id, 2);
        $this->service->updateItemQuantityInCart($this->product3->id, 1);

        $cartItems = $this->service->getCartItems();
        $this->assertEquals(5, $cartItems->where('product_id', $this->product1->id)->first()->quantity);
        $this->assertEquals(2, $cartItems->where('product_id', $this->product2->id)->first()->quantity);
        $this->assertEquals(1, $cartItems->where('product_id', $this->product3->id)->first()->quantity);
        $this->assertEquals(80 * 5 + 150 * 2 + 250 * 1 + 350 * 1, $this->service->getTotalPrice());
    }

    public function test_remove_item_from_cart(): void
    {
        $this->service->addItemToCart($this->product1->id, 2);
        $this->service->addItemToCart($this->product2->id, 1);
        $this->service->addItemToCart($this->product3->id, 3);
        $this->service->addItemToCart($this->product4->id, 1);

        $this->service->removeItemFromCart($this->product1->id);

        $cartItems = $this->service->getCartItems();
        $this->assertCount(3, $cartItems);
        $this->assertNull($cartItems->where('product_id', $this->product1->id)->first());
    }

    public function test_get_total_price(): void
    {
        $this->service->addItemToCart($this->product1->id, 2);
        $this->service->addItemToCart($this->product2->id, 1);
        $this->service->addItemToCart($this->product3->id, 3);
        $this->service->addItemToCart($this->product4->id, 1);

        $this->assertEquals(80 * 2 + 150 * 1 + 250 * 3 + 350 * 1, $this->service->getTotalPrice());
    }

    public function test_get_final_price(): void
    {
        $this->service->addItemToCart($this->product1->id, 2);
        $this->service->addItemToCart($this->product2->id, 1);
        $this->service->addItemToCart($this->product3->id, 3);
        $this->service->addItemToCart($this->product4->id, 1);

        $this->assertEquals(80 * 2 + 150 * 1 + 250 * 3 + 350 * 1, $this->service->getFinalPrice());
    }

    private function makeCartService(): ShoppingCartService
    {
        $request = Request::create('/');
        $request->setLaravelSession($this->app['session.store']);
        $this->app->instance('request', $request);

        return new ShoppingCartService($request);
    }
}
