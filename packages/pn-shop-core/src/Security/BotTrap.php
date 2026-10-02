<?php

namespace PnShop\Security;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Two cheap checks against form-filling bots, with no third-party service:
 *
 * - a honeypot: a field hidden from people that bots fill in;
 * - a time trap: an encrypted "form shown at" time; submissions faster than a person
 *   could type are refused, and so are submissions without it.
 */
final class BotTrap
{
    public const HONEYPOT = 'contact_website';

    public const STARTED = 'form_started';

    /**
     * The fields a protected form sends back, issued when the page is rendered.
     *
     * @return array{contact_website: string, form_started: string}
     */
    public static function fields(?CarbonInterface $at = null): array
    {
        return [self::HONEYPOT => '', self::STARTED => Crypt::encryptString((string) ($at ?? now())->getTimestamp())];
    }

    /**
     * Why the submission looks automated, or null when it passes.
     */
    public static function failure(?string $honeypot, ?string $started): ?string
    {
        if ($honeypot !== null && $honeypot !== '') {
            return 'honeypot';
        }

        try {
            $shownAt = (int) Crypt::decryptString((string) $started);
        } catch (DecryptException) {
            return 'missing_token';
        }

        $elapsed = now()->getTimestamp() - $shownAt;

        if ($elapsed < (int) config('pnshop.security.bot_trap.min_seconds', 2)) {
            return 'too_fast';
        }

        if ($elapsed > (int) config('pnshop.security.bot_trap.max_age_hours', 24) * 3600) {
            return 'expired';
        }

        return null;
    }
}
