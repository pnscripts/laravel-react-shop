<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PnShop\Acl\Models\AdminUser;
use PnShop\Catalog\Models\Product;
use PnShop\Catalog\ProductType;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_administrator(): void
    {
        $this->artisan('pnshop:create-admin', ['email' => 'owner@example.com', '--name' => 'Owner'])
            ->expectsQuestion('Password', 'a-strong-password')
            ->assertSuccessful();

        $admin = AdminUser::query()->where('email', 'owner@example.com')->sole();
        $this->assertTrue($admin->isAdministrator());
        $this->assertTrue(Hash::check('a-strong-password', $admin->password));
        $this->assertSame(0, User::query()->count(), 'Staff accounts are not customer accounts.');
    }

    public function test_it_resets_and_promotes_an_existing_staff_account(): void
    {
        $admin = AdminUser::factory()->inactive()->create(['email' => 'staff@example.com']);

        $this->artisan('pnshop:create-admin', ['email' => 'staff@example.com', '--generate-password' => true])
            ->assertSuccessful();

        $admin->refresh();
        $this->assertTrue($admin->isAdministrator());
        $this->assertTrue($admin->is_active);
        $this->assertSame(1, AdminUser::query()->count());
    }

    public function test_it_rejects_a_short_password(): void
    {
        $this->artisan('pnshop:create-admin', ['email' => 'owner@example.com', '--name' => 'Owner'])
            ->expectsQuestion('Password', 'short')
            ->assertFailed();

        $this->assertSame(0, AdminUser::query()->count());
    }

    public function test_seeding_creates_no_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::query()->count());
        $this->assertSame(0, AdminUser::query()->count());

        // The demo catalog shows variants, brands and filterable attributes.
        $tShirt = Product::query()->where('title', 'Classic T-shirt')->sole();
        $this->assertSame(ProductType::Variable, $tShirt->type);
        $this->assertSame(4, $tShirt->variants()->count());
        $this->get('/shop')->assertOk()->assertInertia(fn ($page) => $page->has('brands', 3)->has('facets'));
    }
}
