<?php

namespace Tests\Feature\Shop;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_is_rate_limited(): void
    {
        $payment = PaymentMethod::factory()->create(['is_active' => true]);
        $data = $this->checkoutData($payment->id, ['email' => 'bot@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('checkout.store'), $data)->assertRedirect();
        }

        $this->post(route('checkout.store'), $data)->assertTooManyRequests();
    }

    public function test_cart_quantity_is_capped_per_line(): void
    {
        $this->post(route('cart.store'), ['product_id' => 1, 'quantity' => 101])
            ->assertSessionHasErrors('quantity');
    }
}
