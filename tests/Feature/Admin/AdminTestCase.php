<?php

namespace Tests\Feature\Admin;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Acl\Models\AdminUser;
use Tests\TestCase;

abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    protected function actingAsAdministrator(): AdminUser
    {
        $admin = AdminUser::factory()->administrator()->create();
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function actingAsStaff(array $permissions = [], ?string $role = null): AdminUser
    {
        $admin = AdminUser::factory()->withPermissions($permissions)->create();

        if ($role !== null) {
            $admin->assignRole($role);
        }

        $this->actingAs($admin, 'admin');

        return $admin;
    }
}
