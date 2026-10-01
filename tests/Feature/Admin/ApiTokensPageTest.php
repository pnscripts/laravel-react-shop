<?php

namespace Tests\Feature\Admin;

use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Livewire;
use PnShop\Acl\Models\AdminUser;
use PnShop\Api\Filament\Pages\ManageApiTokens;

class ApiTokensPageTest extends AdminTestCase
{
    public function test_staff_create_a_token_with_their_permissions_and_revoke_it(): void
    {
        $admin = $this->actingAsStaff(['catalog.products.view', 'sales.orders.view']);

        $this->get(ManageApiTokens::getUrl())->assertOk();

        $page = Livewire::test(ManageApiTokens::class)
            ->callTableAction('create', data: ['name' => 'ERP', 'expires_in' => '30', 'all' => false, 'abilities' => ['sales.orders.view']])
            ->assertHasNoTableActionErrors();

        $token = PersonalAccessToken::query()->sole();
        $this->assertSame(['sales.orders.view'], $token->getAttribute('abilities'));
        $this->assertTrue($token->tokenable->is($admin));
        $this->assertNotNull($token->getAttribute('expires_at'));
        $this->assertStringContainsString('|pnshop_', (string) $page->get('plainTextToken'));

        // The token works against the Admin API.
        $this->withHeader('Authorization', 'Bearer '.$page->get('plainTextToken'))->getJson('/api/admin/v1/orders')->assertOk();

        // API requests forget signed-in guards; sign back in to the panel.
        $this->flushHeaders()->actingAs($admin, 'admin');
        Livewire::test(ManageApiTokens::class)->callTableAction('revoke', $token);
        $this->assertSame(0, PersonalAccessToken::query()->count());
    }

    public function test_permissions_the_owner_lacks_are_not_offered_and_refused(): void
    {
        $this->actingAsStaff(['catalog.products.view']);

        Livewire::test(ManageApiTokens::class)
            ->callTableAction('create', data: ['name' => 'Sneaky', 'expires_in' => '', 'all' => false, 'abilities' => ['system.settings.manage']]);

        $this->assertSame(0, PersonalAccessToken::query()->count());
    }

    public function test_only_own_tokens_are_listed(): void
    {
        $other = AdminUser::factory()->administrator()->create();
        $other->createToken('theirs', ['*']);

        $this->actingAsAdministrator();

        Livewire::test(ManageApiTokens::class)->assertCountTableRecords(0);
    }
}
