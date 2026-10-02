<?php

namespace PnShop\Api\Http\Controllers\Admin;

use Illuminate\Http\Request;
use PnShop\Api\ApiServiceProvider;

class MeController extends AdminController
{
    /**
     * The token's owner and abilities
     *
     * Who the token belongs to and which permissions it carries ("*" = all of the owner's).
     *
     * @return array<string, mixed>
     */
    public function show(Request $request): array
    {
        $admin = $this->admin($request);
        $token = ApiServiceProvider::accessToken($request);

        return ['data' => [
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'token' => [
                'name' => $token?->getAttribute('name'),
                'abilities' => $token?->getAttribute('abilities') ?? [],
                'expires_at' => self::iso($token?->getAttribute('expires_at')),
                'last_used_at' => self::iso($token?->getAttribute('last_used_at')),
            ],
        ]];
    }

    private static function iso(mixed $date): ?string
    {
        return $date instanceof \DateTimeInterface ? $date->format(DATE_ATOM) : null;
    }
}
