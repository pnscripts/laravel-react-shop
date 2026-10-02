<?php

namespace PnShop\Seo\Http;

use Illuminate\Http\Response;
use PnShop\Localization\Localization;
use PnShop\Localization\Models\Language;
use PnShop\Settings\Settings;

class RobotsController
{
    /** Shop areas with nothing for search engines, in every language. */
    private const PRIVATE_PATHS = ['/admin', '/cart', '/checkout', '/account', '/dashboard', '/orders', '/invoices', '/preview', '/login', '/register', '/settings'];

    public function __invoke(Settings $settings, Localization $localization): Response
    {
        $lines = ['User-agent: *'];

        if (! $settings->get('seo.allow_indexing')) {
            $lines[] = 'Disallow: /';
        } else {
            foreach ($localization->languages() as $language) {
                /** @var Language $language */
                $prefix = $localization->prefix($language->code);

                foreach (self::PRIVATE_PATHS as $path) {
                    $lines[] = 'Disallow: '.$prefix.$path;
                }
            }

            $lines[] = 'Disallow: /*?*filter';
            $lines[] = 'Disallow: /*?*sort=';
            $extra = trim((string) $settings->get('seo.robots_extra'));

            if ($extra !== '') {
                $lines[] = $extra;
            }

            $lines[] = '';
            $lines[] = 'Sitemap: '.url('/sitemap.xml');
        }

        return response(implode("\n", array_unique($lines, SORT_REGULAR))."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
