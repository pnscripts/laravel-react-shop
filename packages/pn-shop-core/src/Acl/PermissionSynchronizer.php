<?php

namespace PnShop\Acl;

use Illuminate\Support\Str;
use PnShop\Foundation\Extension\PermissionRegistry;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Writes the permission registry to the database and keeps the built-in roles present.
 * Permissions that are no longer registered (e.g. a disabled plugin) are kept, so
 * re-enabling an extension restores existing role assignments.
 */
final class PermissionSynchronizer
{
    public const GUARD = 'admin';

    public function __construct(
        private PermissionRegistry $registry,
        private PermissionRegistrar $registrar,
    ) {}

    /**
     * @return list<string> keys of permissions created by this run
     */
    public function sync(): array
    {
        $existing = Permission::query()->where('guard_name', self::GUARD)->pluck('name')->all();
        $created = [];

        foreach ($this->registry->all() as $key => $permission) {
            if (! in_array($key, $existing, true)) {
                Permission::query()->create(['name' => $key, 'guard_name' => self::GUARD]);
                $created[] = $key;
            }
        }

        foreach (DefaultRoles::all() as $roleName => $patterns) {
            $role = Role::findOrCreate($roleName, self::GUARD);

            $grant = array_values(array_filter($created, fn (string $key) => Str::is($patterns, $key)));

            if ($grant !== []) {
                $role->givePermissionTo($grant);
            }
        }

        $this->registrar->forgetCachedPermissions();

        return $created;
    }
}
