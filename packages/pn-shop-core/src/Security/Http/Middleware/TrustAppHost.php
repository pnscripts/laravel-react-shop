<?php

namespace PnShop\Security\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts;
use PnShop\Installer\Installation;

/**
 * Only answers requests for the shop's own host (APP_URL and its subdomains, plus
 * PNSHOP_TRUSTED_HOSTS). A forged Host header could otherwise put another domain into
 * password reset emails, signed links and the cached sitemap.
 *
 * Skipped until the shop is installed (APP_URL is set by the installer), and in the local
 * environment and tests.
 */
class TrustAppHost extends TrustHosts
{
    /**
     * @return array<int, string|null>
     */
    public function hosts(): array
    {
        $extra = array_filter(array_map('trim', explode(',', (string) config('pnshop.security.trusted_hosts'))));

        return [
            $this->allSubdomainsOfApplicationUrl(),
            ...array_map(fn (string $host) => '^'.preg_quote($host).'$', $extra),
        ];
    }

    protected function shouldSpecifyTrustedHosts()
    {
        return parent::shouldSpecifyTrustedHosts() && app(Installation::class)->isInstalled();
    }
}
