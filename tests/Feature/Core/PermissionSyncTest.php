<?php

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PnShop\Acl\PermissionSynchronizer;
use PnShop\Foundation\Extension\Permission;
use PnShop\Foundation\Extension\PermissionRegistry;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrating_stores_core_permissions_and_built_in_roles(): void
    {
        $this->assertTrue(PermissionModel::query()->where(['name' => 'catalog.products.update', 'guard_name' => 'admin'])->exists());

        foreach (['administrator', 'manager', 'content-editor', 'catalog-manager', 'order-manager', 'customer-manager'] as $role) {
            $this->assertNotNull(Role::findByName($role, 'admin'));
        }

        $catalogManager = Role::findByName('catalog-manager', 'admin');
        $this->assertTrue($catalogManager->hasPermissionTo('catalog.products.update'));
        $this->assertFalse($catalogManager->hasPermissionTo('sales.orders.view'));
    }

    public function test_new_permissions_join_matching_built_in_roles_once(): void
    {
        $registry = app(PermissionRegistry::class);
        $registry->register(new Permission('catalog.brands.manage', 'Manage brands', 'Catalog'));

        app(PermissionSynchronizer::class)->sync();

        $catalogManager = Role::findByName('catalog-manager', 'admin');
        $this->assertTrue($catalogManager->hasPermissionTo('catalog.brands.manage'));

        // A later manual removal is respected by subsequent syncs.
        $catalogManager->revokePermissionTo('catalog.brands.manage');
        app(PermissionSynchronizer::class)->sync();

        $this->assertFalse($catalogManager->fresh()->hasPermissionTo('catalog.brands.manage'));
    }

    public function test_permission_keys_must_be_dotted_lowercase(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(PermissionRegistry::class)->register(new Permission('ManageEverything', 'Bad', 'X'));
    }
}
