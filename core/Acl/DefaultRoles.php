<?php

namespace PnShop\Acl;

use PnShop\Acl\Models\AdminUser;

/**
 * Built-in staff roles. Each maps to permission key patterns; a permission that
 * first appears during a sync is granted to every built-in role whose pattern
 * matches it. Later edits to a role in the admin are never overwritten.
 */
final class DefaultRoles
{
    /**
     * @return array<string, list<string>> role name => permission patterns
     */
    public static function all(): array
    {
        return [
            AdminUser::ADMINISTRATOR_ROLE => ['*'],
            'manager' => ['catalog.*', 'content.*', 'sales.*', 'customers.*', 'marketing.*'],
            'content-editor' => ['content.*'],
            'catalog-manager' => ['catalog.*'],
            'order-manager' => ['sales.*'],
            'customer-manager' => ['customers.*'],
        ];
    }
}
