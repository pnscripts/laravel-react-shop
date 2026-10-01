<?php

use PnShop\Acl\AclServiceProvider;
use PnShop\Admin\AdminServiceProvider;
use PnShop\Catalog\CatalogServiceProvider;
use PnShop\Localization\LocalizationServiceProvider;
use PnShop\Media\MediaServiceProvider;
use PnShop\Sales\SalesServiceProvider;
use PnShop\Settings\SettingsServiceProvider;
use PnShop\System\SystemServiceProvider;

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
        SalesServiceProvider::class,
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

];
