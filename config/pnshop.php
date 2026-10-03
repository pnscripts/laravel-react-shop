<?php

/*
|--------------------------------------------------------------------------
| PN Shop configuration: your overrides only
|--------------------------------------------------------------------------
|
| Every option, with its explanation and default, is in the core package:
| vendor/pnscripts/pn-shop-core/config/pnshop.php. Most are set from .env
| (TRUSTED_PROXIES, PNSHOP_TRUSTED_HOSTS, PNSHOP_EXTENSION_UPLOADS, …).
|
| Copy here only the options you change. Your values win over the core's, at
| any depth, and options added by later updates keep their defaults.
|
*/

return [

    // Your own module service providers (classes extending PnShop\Foundation\ModuleServiceProvider),
    // booted after the core modules.
    'extra_modules' => [],

];
