<?php

namespace Tests\Feature\Core;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PnShop\Acl\Models\AdminUser;
use Tests\TestCase;

class AdminFlagMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_flagged_users_become_administrators_and_the_flag_is_dropped(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_admin')->default(false));

        $hash = Hash::make('old-password');
        DB::table('users')->insert([
            ['name' => 'Owner', 'email' => 'owner@example.com', 'password' => $hash, 'is_admin' => true],
            ['name' => 'Buyer', 'email' => 'buyer@example.com', 'password' => $hash, 'is_admin' => false],
        ]);

        $migration = require base_path('packages/pn-shop-core/src/Acl/database/migrations/2026_10_01_120000_move_admin_flag_users_to_admin_users.php');
        $migration->up();

        $admin = AdminUser::query()->sole();
        $this->assertSame('owner@example.com', $admin->email);
        $this->assertSame($hash, $admin->password);
        $this->assertTrue($admin->isAdministrator());
        $this->assertFalse(Schema::hasColumn('users', 'is_admin'));
        $this->assertSame(2, DB::table('users')->count(), 'Customer accounts are kept.');
    }
}
