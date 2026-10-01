<?php

use PnShop\Acl\AclServiceProvider;
use PnShop\Admin\AdminServiceProvider;
use PnShop\Api\ApiServiceProvider;
use PnShop\Cart\CartServiceProvider;
use PnShop\Catalog\CatalogServiceProvider;
use PnShop\Cms\CmsServiceProvider;
use PnShop\Customer\CustomerServiceProvider;
use PnShop\Extension\ExtensionServiceProvider;
use PnShop\Installer\InstallerServiceProvider;
use PnShop\Inventory\InventoryServiceProvider;
use PnShop\Localization\LocalizationServiceProvider;
use PnShop\Media\MediaServiceProvider;
use PnShop\Payment\PaymentServiceProvider;
use PnShop\Promotion\PromotionServiceProvider;
use PnShop\Returns\ReturnsServiceProvider;
use PnShop\Sales\SalesServiceProvider;
use PnShop\Security\SecurityServiceProvider;
use PnShop\Seo\SeoServiceProvider;
use PnShop\Settings\SettingsServiceProvider;
use PnShop\Shipping\ShippingServiceProvider;
use PnShop\System\SystemServiceProvider;
use PnShop\Tax\TaxServiceProvider;
use PnShop\Theme\ThemeServiceProvider;

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
        PromotionServiceProvider::class,
        ReturnsServiceProvider::class,
        CmsServiceProvider::class,
        ExtensionServiceProvider::class,
        ThemeServiceProvider::class,
        SeoServiceProvider::class,
        ApiServiceProvider::class,
        AdminServiceProvider::class,
        InstallerServiceProvider::class,
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

    /*
    |--------------------------------------------------------------------------
    | Extensions
    |--------------------------------------------------------------------------
    |
    | Plugins live in `path` (<vendor>/<name>/pnshop.json) or come from Composer
    | packages of type "pnshop-plugin". Enabled plugins are booted from `cache`.
    | Safe mode boots no plugins at all, so a broken one can be removed.
    | Plugins run with full application privileges: install only code you trust.
    |
    */

    'extensions' => [
        'path' => env('PNSHOP_EXTENSIONS_PATH', base_path('extensions')),
        'cache' => env('PNSHOP_PLUGIN_CACHE', base_path('bootstrap/cache/pnshop-plugins.php')),
        'safe_mode' => (bool) env('PNSHOP_SAFE_MODE', false),
        // Zip uploads in the admin; off by default.
        'uploads' => (bool) env('PNSHOP_EXTENSION_UPLOADS', false),
        // Refuse plugins without a valid signature from one of the trusted keys.
        'require_signatures' => (bool) env('PNSHOP_REQUIRE_SIGNATURES', false),
        // key id => base64 Ed25519 public key
        'trusted_keys' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Themes
    |--------------------------------------------------------------------------
    |
    | Storefront themes live in `path` (<vendor>/<name>/pnshop.json). A theme
    | ships a prebuilt bundle in dist/, published to public/themes/<id>/build.
    |
    */

    'themes' => [
        'path' => env('PNSHOP_THEMES_PATH', base_path('themes')),
    ],

    /*
    |--------------------------------------------------------------------------
    | APIs
    |--------------------------------------------------------------------------
    |
    | Requests per minute for each token (or IP address without a token) on
    | the Store API (/api/store/v1) and the Admin API (/api/admin/v1).
    |
    */

    'api' => [
        'store_rate_limit' => (int) env('PNSHOP_STORE_API_RATE_LIMIT', 120),
        'admin_rate_limit' => (int) env('PNSHOP_ADMIN_API_RATE_LIMIT', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Installer
    |--------------------------------------------------------------------------
    |
    | Until PN Shop is installed every page leads to the web installer
    | (/install). `enforce` is off in the test suite. The lock file marks
    | the installation; delete it only together with the database.
    |
    */

    'installer' => [
        'enforce' => (bool) env('PNSHOP_ENFORCE_INSTALL', true),
        'lock' => env('PNSHOP_INSTALL_LOCK', storage_path('app/pnshop-installed.json')),
    ],

];
