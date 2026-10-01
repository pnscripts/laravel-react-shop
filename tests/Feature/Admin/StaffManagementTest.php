<?php

namespace Tests\Feature\Admin;

use Livewire\Livewire;
use PnShop\Acl\Filament\Resources\AdminUsers\Pages\CreateAdminUser;
use PnShop\Acl\Filament\Resources\AdminUsers\Pages\EditAdminUser;
use PnShop\Acl\Filament\Resources\Roles\Pages\EditRole;
use PnShop\Acl\Models\AdminUser;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StaffManagementTest extends AdminTestCase
{
    public function test_staff_can_be_created_with_a_role(): void
    {
        $this->actingAsAdministrator();
        $role = Role::findByName('order-manager', 'admin');

        Livewire::test(CreateAdminUser::class)
            ->fillForm([
                'name' => 'Olga',
                'email' => 'olga@example.com',
                'password' => 'a-long-password',
                'roles' => [$role->id],
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $staff = AdminUser::query()->where('email', 'olga@example.com')->sole();
        $this->assertTrue($staff->hasRole('order-manager'));
        $this->assertTrue($staff->can('sales.orders.view'));
        $this->assertFalse($staff->can('catalog.products.update'));
    }

    public function test_nobody_can_delete_their_own_account(): void
    {
        $admin = $this->actingAsAdministrator();
        AdminUser::factory()->administrator()->create();

        Livewire::test(EditAdminUser::class, ['record' => $admin->getRouteKey()])
            ->assertActionHidden('delete');
    }

    public function test_the_last_administrator_cannot_be_demoted(): void
    {
        $admin = $this->actingAsAdministrator();

        Livewire::test(EditAdminUser::class, ['record' => $admin->getRouteKey()])
            ->fillForm(['roles' => []])
            ->call('save');

        $this->assertTrue($admin->fresh()->isAdministrator());
    }

    public function test_an_administrator_can_be_demoted_while_another_remains(): void
    {
        $this->actingAsAdministrator();
        $other = AdminUser::factory()->administrator()->create();

        Livewire::test(EditAdminUser::class, ['record' => $other->getRouteKey()])
            ->fillForm(['roles' => []])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($other->fresh()->isAdministrator());
    }

    public function test_the_administrator_role_cannot_be_edited(): void
    {
        $this->actingAsAdministrator();
        $role = Role::findByName(AdminUser::ADMINISTRATOR_ROLE, 'admin');

        $this->get("/admin/roles/{$role->id}/edit")->assertForbidden();
    }

    public function test_custom_roles_can_be_edited(): void
    {
        $this->actingAsAdministrator();
        $role = Role::findByName('content-editor', 'admin');
        $permission = Permission::findByName('catalog.products.view', 'admin');

        Livewire::test(EditRole::class, ['record' => $role->getRouteKey()])
            ->fillForm(['permissions' => [$permission->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($role->fresh()->hasPermissionTo('catalog.products.view'));
    }
}
