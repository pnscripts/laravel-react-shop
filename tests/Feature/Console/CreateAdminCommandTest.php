<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_administrator(): void
    {
        $this->artisan('pnshop:create-admin', ['email' => 'owner@example.com', '--name' => 'Owner'])
            ->expectsQuestion('Password', 'a-strong-password')
            ->assertSuccessful();

        $user = User::query()->where('email', 'owner@example.com')->sole();
        $this->assertTrue($user->isAdmin());
        $this->assertTrue(Hash::check('a-strong-password', $user->password));
    }

    public function test_it_promotes_an_existing_user(): void
    {
        $user = User::factory()->create(['email' => 'staff@example.com']);

        $this->artisan('pnshop:create-admin', ['email' => 'staff@example.com', '--generate-password' => true])
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->isAdmin());
        $this->assertSame(1, User::query()->count());
    }

    public function test_it_rejects_a_short_password(): void
    {
        $this->artisan('pnshop:create-admin', ['email' => 'owner@example.com', '--name' => 'Owner'])
            ->expectsQuestion('Password', 'short')
            ->assertFailed();

        $this->assertSame(0, User::query()->count());
    }

    public function test_seeding_creates_no_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::query()->count());
    }
}
