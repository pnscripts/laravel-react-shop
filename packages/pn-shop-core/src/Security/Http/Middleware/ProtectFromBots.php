<?php

namespace PnShop\Security\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PnShop\Security\BotTrap;
use PnShop\Security\Captcha\CaptchaVerifier;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware `bot-trap` for public forms (checkout, registration): honeypot,
 * time trap and the bound CaptchaVerifier.
 */
class ProtectFromBots
{
    public function __construct(private CaptchaVerifier $captcha) {}

    public function handle(Request $request, Closure $next): Response
    {
        $failure = BotTrap::failure($request->input(BotTrap::HONEYPOT), $request->input(BotTrap::STARTED));

        if ($failure === null && ! $this->captcha->verify($request)) {
            $failure = 'captcha';
        }

        if ($failure !== null) {
            Log::notice('Form submission refused as automated.', ['reason' => $failure, 'path' => $request->path(), 'ip' => $request->ip()]);

            throw ValidationException::withMessages([
                'form' => __('We could not verify your submission. Please wait a moment and try again.'),
            ]);
        }

        $request->request->remove(BotTrap::HONEYPOT);
        $request->request->remove(BotTrap::STARTED);

        return $next($request);
    }
}
