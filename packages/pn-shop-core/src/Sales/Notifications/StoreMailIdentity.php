<?php

namespace PnShop\Sales\Notifications;

use PnShop\Settings\Settings;

/**
 * Laravel's mail layout prints config('app.name') in the header and footer; emails
 * should carry the store's name instead.
 */
final class StoreMailIdentity
{
    public static function apply(): void
    {
        $name = trim((string) app(Settings::class)->get('store.name'));

        if ($name !== '') {
            config(['app.name' => $name]);
        }
    }
}
