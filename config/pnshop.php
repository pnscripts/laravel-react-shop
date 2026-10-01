<?php

use PnShop\Acl\AclServiceProvider;
use PnShop\Admin\AdminServiceProvider;
use PnShop\Catalog\CatalogServiceProvider;
use PnShop\Localization\LocalizationServiceProvider;
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
        AclServiceProvider::class,
        SystemServiceProvider::class,
        CatalogServiceProvider::class,
        SalesServiceProvider::class,
        AdminServiceProvider::class,
    ],

];
