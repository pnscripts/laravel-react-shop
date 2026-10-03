<?php

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use PnShop\Foundation\PnShopServiceProvider;

class ConfigMergeTest extends TestCase
{
    public function test_shop_values_win_at_any_depth_and_new_defaults_still_arrive(): void
    {
        $defaults = [
            'security' => ['order_link_days' => 180, 'trusted_hosts' => '', 'new_option' => true],
            'trusted_proxies' => '',
            'extra_modules' => [],
            'media' => ['sizes' => [320, 800, 1600]],
        ];

        // A shop whose config/pnshop.php is a full copy of an older version, with two changes.
        $shop = [
            'security' => ['order_link_days' => 30, 'trusted_hosts' => ''],
            'extra_modules' => ['App\\Modules\\Loyalty'],
            'media' => ['sizes' => [400]],
        ];

        $this->assertSame([
            'security' => ['order_link_days' => 30, 'trusted_hosts' => '', 'new_option' => true],
            'trusted_proxies' => '',
            'extra_modules' => ['App\\Modules\\Loyalty'],
            'media' => ['sizes' => [400]],
        ], PnShopServiceProvider::mergeConfig($defaults, $shop));
    }
}
