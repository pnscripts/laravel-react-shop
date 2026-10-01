<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PnShop\Acl\Models\AdminUser;
use PnShop\Installer\Installation;
use PnShop\Security\Http\Middleware\TrustAppHost;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Tests\TestCase;

class TrustedHostsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Symfony keeps trusted host patterns in a static; do not leak them into other tests.
        Request::setTrustedHosts([]);

        parent::tearDown();
    }

    private function middleware(): TrustAppHost
    {
        // The real check is off in tests (and locally); exercise it as in production.
        return new class($this->app) extends TrustAppHost
        {
            protected function shouldSpecifyTrustedHosts()
            {
                return app(Installation::class)->isInstalled();
            }
        };
    }

    public function test_installed_shops_answer_only_their_own_host(): void
    {
        config(['app.url' => 'https://shop.example', 'pnshop.security.trusted_hosts' => 'www.shop-alias.test']);
        AdminUser::factory()->create();

        $hosts = $this->middleware()->hosts();

        $this->assertMatchesRegularExpression('#'.$hosts[0].'#', 'shop.example');
        $this->assertMatchesRegularExpression('#'.$hosts[0].'#', 'cdn.shop.example');
        $this->assertDoesNotMatchRegularExpression('#'.$hosts[0].'#', 'evil.tld');
        $this->assertMatchesRegularExpression('#'.$hosts[1].'#', 'www.shop-alias.test');

        $request = Request::create('https://evil.tld/forgot-password');
        $this->middleware()->handle($request, fn () => response('ok'));

        $this->expectException(SuspiciousOperationException::class);
        $request->getHost();
    }

    public function test_not_enforced_before_installation(): void
    {
        $request = Request::create('https://new-host.example/install');
        $this->middleware()->handle($request, fn () => response('ok'));

        $this->assertSame('new-host.example', $request->getHost());
    }
}
