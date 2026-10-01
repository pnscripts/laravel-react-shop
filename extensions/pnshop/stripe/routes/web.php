<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use PnShop\Plugins\Stripe\Http\StripeController;

Route::get('/stripe/return/{payment}', [StripeController::class, 'return'])->whereNumber('payment')->middleware('throttle:30,1')->name('stripe.return');

// Called by Stripe's servers: no session or CSRF token; the signature authenticates it.
Route::post('/stripe/webhook', [StripeController::class, 'webhook'])
    ->withoutMiddleware([PreventRequestForgery::class, VerifyCsrfToken::class])
    ->middleware('throttle:120,1')
    ->name('stripe.webhook');
