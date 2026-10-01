<?php

namespace Tests\Feature\Customers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PnShop\Customer\Models\CustomerAddress;
use PnShop\Customer\Models\CustomerGroup;
use PnShop\Sales\Models\Order;
use PnShop\Security\BotTrap;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_customers_join_the_default_group(): void
    {
        $this->post('/register', ['name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'a-long-password', 'password_confirmation' => 'a-long-password', ...BotTrap::fields(now()->subMinute())]);

        $this->assertSame(CustomerGroup::default()->id, User::query()->where('email', 'ana@example.com')->sole()->customer_group_id);
    }

    public function test_customers_see_only_their_own_orders(): void
    {
        $customer = User::factory()->create();
        $mine = Order::factory()->create(['user_id' => $customer->id]);
        Order::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs($customer)->get('/account/orders')->assertInertia(fn (Assert $page) => $page
            ->component('account/orders')
            ->has('orders.data', 1)
            ->where('orders.data.0.id', $mine->id)
        );
    }

    public function test_the_first_address_becomes_the_default_and_more_can_be_added(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->post('/account/addresses', $this->address(['city' => 'Sofia']))->assertSessionHasNoErrors();
        $this->actingAs($customer)->post('/account/addresses', $this->address(['city' => 'Plovdiv']))->assertSessionHasNoErrors();

        $first = CustomerAddress::query()->where('city', 'Sofia')->sole();
        $this->assertTrue($first->is_default_shipping && $first->is_default_billing);
        $this->assertFalse(CustomerAddress::query()->where('city', 'Plovdiv')->sole()->is_default_shipping);

        $this->actingAs($customer)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('defaultAddress', fn ($lines) => collect($lines)->contains('1000 Sofia'))
        );
    }

    public function test_the_default_address_can_be_changed(): void
    {
        $customer = User::factory()->create();
        $first = CustomerAddress::factory()->for($customer)->create(['is_default_shipping' => true, 'is_default_billing' => true]);
        $second = CustomerAddress::factory()->for($customer)->create();

        $this->actingAs($customer)->post("/account/addresses/{$second->id}/default", ['for' => 'shipping']);

        $this->assertTrue($second->fresh()->is_default_shipping);
        $this->assertFalse($first->fresh()->is_default_shipping);
        $this->assertTrue($first->fresh()->is_default_billing);
    }

    public function test_addresses_are_validated(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/account/addresses', $this->address(['country_code' => 'ZZ', 'line1' => '']))
            ->assertSessionHasErrors(['country_code', 'line1']);
    }

    public function test_customers_cannot_touch_someone_elses_address(): void
    {
        $address = CustomerAddress::factory()->create();

        $this->actingAs(User::factory()->create())->put("/account/addresses/{$address->id}", $this->address())->assertNotFound();
        $this->actingAs(User::factory()->create())->delete("/account/addresses/{$address->id}")->assertNotFound();

        $this->assertModelExists($address);
    }

    public function test_the_account_needs_a_signed_in_customer(): void
    {
        $this->get('/account/orders')->assertRedirect('/login');
        $this->get('/account/addresses')->assertRedirect('/login');
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function address(array $overrides = []): array
    {
        return [...['first_name' => 'Ana', 'last_name' => 'Petrova', 'line1' => 'Vitosha 1', 'city' => 'Sofia', 'postcode' => '1000', 'country_code' => 'BG', 'phone' => '0888'], ...$overrides];
    }
}
