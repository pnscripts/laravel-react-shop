<?php

namespace Tests\Feature\Shop;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CheckoutIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private PaymentMethod $payment;

    protected function setUp(): void
    {
        parent::setUp();

        OrderStatus::factory()->create(['name' => 'pending']);
        $this->payment = PaymentMethod::factory()->create(['is_active' => true]);
    }

    public function test_checkout_charges_the_current_price_not_the_price_when_added(): void
    {
        $product = Product::factory()->active()->create(['price' => 100, 'discount_price' => 10, 'stock' => 5]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);

        $product->update(['discount_price' => null, 'price' => 120]);

        $this->get(route('cart.index'))->assertInertia(fn (Assert $page) => $page
            ->where('cart.items.0.price', 120)
            ->where('cart.final_price', 120)
        );

        $this->post(route('checkout.store'), $this->checkoutData())->assertRedirect();

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'price' => 120,
            'discount_price' => 0,
        ]);
    }

    public function test_repeated_adds_cannot_exceed_stock(): void
    {
        $product = Product::factory()->active()->create(['stock' => 5]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 4])->assertSessionHasNoErrors();
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2])->assertSessionHasErrors('quantity');

        $this->get(route('cart.index'))->assertInertia(fn (Assert $page) => $page
            ->where('cart.items.0.quantity', 4)
        );
    }

    public function test_checkout_fails_cleanly_when_stock_ran_out_after_adding(): void
    {
        $product = Product::factory()->active()->create(['stock' => 3]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 3]);
        $product->update(['stock' => 1]);

        $this->post(route('checkout.store'), $this->checkoutData())
            ->assertRedirect()
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'Not enough stock'));

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_deactivated_products_drop_out_of_the_cart_and_block_checkout(): void
    {
        $product = Product::factory()->active()->create(['stock' => 3]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);
        $product->update(['is_active' => false]);

        $this->get(route('cart.index'))->assertInertia(fn (Assert $page) => $page->has('cart.items', 0));

        $this->post(route('checkout.store'), $this->checkoutData())
            ->assertSessionHas('error');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_session_holds_only_product_ids_and_quantities(): void
    {
        $product = Product::factory()->active()->create(['stock' => 3]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);

        $this->assertSame([$product->id => 2], session('cart.lines'));
    }

    /**
     * @return array<string, mixed>
     */
    private function checkoutData(): array
    {
        return [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '0888123456',
            'address' => '123 Main St',
            'payment_method_id' => $this->payment->id,
        ];
    }
}
