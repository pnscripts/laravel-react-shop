<?php

namespace PnShop\Cms\Blocks;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Cleans rich text for the storefront: formatting, links, lists, tables and images stay;
 * scripts, event handlers, styles and unsafe URLs go.
 */
final class SafeHtml
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function clean(?string $html): string
    {
        self::$sanitizer ??= new HtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowSafeElements()
                ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
                ->allowMediaSchemes(['https', 'http'])
                ->allowRelativeLinks()
                ->allowRelativeMedias()
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
                ->withMaxInputLength(500_000),
        );

        return self::$sanitizer->sanitize((string) $html);
    }
}
