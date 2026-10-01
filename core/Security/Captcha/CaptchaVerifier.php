<?php

namespace PnShop\Security\Captcha;

use Illuminate\Http\Request;

/**
 * Checks a CAPTCHA answer sent with a protected form. The core ships a verifier that
 * accepts everything; an extension can bind its own (Turnstile, hCaptcha, reCAPTCHA, ...)
 * and add its widget to the storefront forms.
 */
interface CaptchaVerifier
{
    public function verify(Request $request): bool;
}
