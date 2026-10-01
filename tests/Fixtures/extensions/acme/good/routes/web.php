<?php

use Illuminate\Support\Facades\Route;
use PnShop\Settings\Settings;

Route::get('/acme-good', fn () => 'good plugin says '.app(Settings::class)->get('plugin.acme_good.greeting'));
