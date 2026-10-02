<?php

namespace PnShop\Foundation\Extension;

use InvalidArgumentException;

/**
 * Collects the permissions declared by core modules and enabled extensions.
 * The registry is the source of truth; `pnshop:permissions:sync` writes it to the database.
 */
final class PermissionRegistry
{
    /** @var array<string, Permission> */
    private array $permissions = [];

    public function register(Permission ...$permissions): void
    {
        foreach ($permissions as $permission) {
            if (! preg_match('/^[a-z0-9_]+(\.[a-z0-9_]+)+$/', $permission->key)) {
                throw new InvalidArgumentException("Invalid permission key [{$permission->key}]. Use dotted lowercase segments, e.g. catalog.products.update.");
            }

            $this->permissions[$permission->key] = $permission;
        }
    }

    public function has(string $key): bool
    {
        return isset($this->permissions[$key]);
    }

    /**
     * @return array<string, Permission>
     */
    public function all(): array
    {
        $permissions = $this->permissions;
        ksort($permissions);

        return $permissions;
    }

    /**
     * Permissions grouped by their group label, for building role forms.
     *
     * @return array<string, list<Permission>>
     */
    public function grouped(): array
    {
        $groups = [];

        foreach ($this->all() as $permission) {
            $groups[$permission->group][] = $permission;
        }

        return $groups;
    }
}
