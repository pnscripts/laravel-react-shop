<?php

namespace PnShop\Security;

use Illuminate\Routing\Router;
use PnShop\Foundation\ModuleServiceProvider;
use PnShop\Security\Captcha\CaptchaVerifier;
use PnShop\Security\Captcha\NullCaptchaVerifier;
use PnShop\Security\Http\Middleware\ProtectFromBots;

/**
 * Protection of public forms against bots.
 */
class SecurityServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bindIf(CaptchaVerifier::class, NullCaptchaVerifier::class);
    }

    protected function bootModule(): void
    {
        $this->app->make(Router::class)->aliasMiddleware('bot-trap', ProtectFromBots::class);
    }
}
