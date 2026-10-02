<?php

namespace PnShop\Security\Captcha;

use Illuminate\Http\Request;

final class NullCaptchaVerifier implements CaptchaVerifier
{
    public function verify(Request $request): bool
    {
        return true;
    }
}
