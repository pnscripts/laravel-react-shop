<?php

namespace PnShop\Api;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use PnShop\Acl\Models\AdminUser;
use PnShop\Foundation\Extension\PermissionRegistry;

/**
 * Admin API tokens. A token's abilities are permission keys, limited to permissions its
 * owner holds; "*" stands for all of the owner's permissions (also ones granted later).
 * The owner's current permissions are always checked too, so taking a role away from an
 * admin also narrows their tokens.
 */
final class StaffTokens
{
    public const ALL = '*';

    public function __construct(private PermissionRegistry $permissions) {}

    /**
     * Permission keys the admin may put on a token.
     *
     * @return list<string>
     */
    public function grantable(AdminUser $admin): array
    {
        return array_values(array_filter(
            array_keys($this->permissions->all()),
            fn (string $permission) => $admin->isAdministrator() || $admin->checkPermissionTo($permission),
        ));
    }

    /**
     * @param  list<string>  $abilities  permission keys, or ["*"]
     *
     * @throws ValidationException when an ability is unknown or not held by the admin
     */
    public function issue(AdminUser $admin, string $name, array $abilities, ?Carbon $expiresAt = null): NewAccessToken
    {
        $abilities = array_values(array_unique($abilities));

        if ($abilities === []) {
            throw ValidationException::withMessages(['abilities' => __('Choose at least one permission.')]);
        }

        if ($abilities !== [self::ALL]) {
            $refused = array_diff($abilities, $this->grantable($admin));

            if ($refused !== []) {
                throw ValidationException::withMessages(['abilities' => __('Not allowed for this account: :permissions', ['permissions' => implode(', ', $refused)])]);
            }
        }

        $token = $admin->createToken($name, $abilities, $expiresAt);

        activity('system')->causedBy($admin)->performedOn($admin)
            ->withProperties(['token' => $name, 'abilities' => $abilities, 'expires_at' => $expiresAt?->toIso8601String()])
            ->log('API token created');

        return $token;
    }
}
