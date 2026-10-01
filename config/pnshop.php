<?php

use PnShop\Acl\AclServiceProvider;
use PnShop\Admin\AdminServiceProvider;
use PnShop\Cart\CartServiceProvider;
use PnShop\Catalog\CatalogServiceProvider;
use PnShop\Cms\CmsServiceProvider;
use PnShop\Customer\CustomerServiceProvider;
use PnShop\Inventory\InventoryServiceProvider;
use PnShop\Localization\LocalizationServiceProvider;
use PnShop\Media\MediaServiceProvider;
use PnShop\Payment\PaymentServiceProvider;
use PnShop\Sales\SalesServiceProvider;
use PnShop\Security\SecurityServiceProvider;
use PnShop\Seo\SeoServiceProvider;
use PnShop\Settings\SettingsServiceProvider;
use PnShop\Shipping\ShippingServiceProvider;
use PnShop\System\SystemServiceProvider;
use PnShop\Tax\TaxServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Core modules
    |--------------------------------------------------------------------------
    |
    | Service providers of the PN Shop core modules, booted in this order.
    | Extensions (plugins and themes) are not listed here; they are loaded by
    | the extension manager.
    |
    */

    'modules' => [
        SettingsServiceProvider::class,
        LocalizationServiceProvider::class,
        MediaServiceProvider::class,
        AclServiceProvider::class,
        SystemServiceProvider::class,
        CatalogServiceProvider::class,
        InventoryServiceProvider::class,
        CustomerServiceProvider::class,
        CartServiceProvider::class,
        SecurityServiceProvider::class,
        SalesServiceProvider::class,
        PaymentServiceProvider::class,
        ShippingServiceProvider::class,
        TaxServiceProvider::class,
        CmsServiceProvider::class,
        SeoServiceProvider::class,
        AdminServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    |
    | Uploads are stored on this filesystem disk (run `php artisan storage:link`
    | for the default "public" disk). Images get resized WebP conversions no
    | wider than the given pixel widths.
    |
    */

    'media' => [
        'disk' => env('PNSHOP_MEDIA_DISK', 'public'),
        'conversions' => [
            'thumb' => 320,
            'medium' => 800,
            'large' => 1600,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Form protection
    |--------------------------------------------------------------------------
    |
    | Checkout and registration refuse submissions that fill the hidden
    | honeypot field, arrive faster than `min_seconds` after the form was
    | shown, or use a form older than `max_age_hours`.
    |
    */

    'security' => [
        'bot_trap' => [
            'min_seconds' => (int) env('PNSHOP_BOT_TRAP_MIN_SECONDS', 2),
            'max_age_hours' => 24,
        ],
    ],

];
