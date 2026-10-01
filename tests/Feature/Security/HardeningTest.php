<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PnShop\Acl\Filament\Resources\AdminUsers\Pages\EditAdminUser;
use PnShop\Acl\Filament\Resources\AdminUsers\Schemas\AdminUserForm;
use PnShop\Acl\Models\AdminUser;
use PnShop\Catalog\Models\Product;
use PnShop\Cms\Exceptions\LockedBlocksException;
use PnShop\Cms\Models\Page;
use PnShop\Cms\PageRevisions;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Sales\Models\Order;
use PnShop\Sales\OrderLinks;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\PaymentStatus;
use PnShop\Shipping\ShipmentService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression tests for the 1.0 security review.
 */
class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_managers_cannot_promote_themselves_or_take_over_administrators(): void
    {
        $manager = AdminUser::factory()->withPermissions(['system.admin_users.manage'])->create();
        $administrator = AdminUser::factory()->administrator()->create();
        $editors = Role::findOrCreate('Editors', 'admin');
        $editors->givePermissionTo('content.pages.manage');
        $managers = Role::findOrCreate('Account managers', 'admin');
        $managers->givePermissionTo('system.admin_users.manage');

        $this->assertFalse($manager->can('update', $administrator));
        $this->assertFalse($manager->can('delete', $administrator));
        $this->assertTrue($manager->can('update', AdminUser::factory()->create()));

        $this->actingAs($manager, 'admin');
        $assignable = AdminUserForm::assignableRoleIds();

        $this->assertNotContains(Role::findByName(AdminUser::ADMINISTRATOR_ROLE, 'admin')->id, $assignable);
        $this->assertNotContains($editors->id, $assignable, 'A role with permissions the manager lacks.');
        $this->assertContains($managers->id, $assignable);

        Livewire::test(EditAdminUser::class, ['record' => $manager->id])
            ->fillForm(['roles' => [Role::findByName(AdminUser::ADMINISTRATOR_ROLE, 'admin')->id]])
            ->call('save')
            ->assertHasFormErrors(['roles']);

        $this->assertFalse($manager->fresh()->isAdministrator());
    }

    public function test_role_managers_cannot_edit_roles_beyond_their_own_permissions(): void
    {
        $manager = AdminUser::factory()->withPermissions(['system.roles.manage'])->create();
        $powerful = Role::findOrCreate('Shop managers', 'admin');
        $powerful->givePermissionTo(['system.settings.manage']);
        $harmless = Role::findOrCreate('Role clerks', 'admin');
        $harmless->givePermissionTo(['system.roles.manage']);

        $this->assertFalse($manager->can('update', $powerful));
        $this->assertTrue($manager->can('update', $harmless));
    }

    public function test_restoring_a_revision_cannot_bring_back_html_blocks_without_the_permission(): void
    {
        $admin = AdminUser::factory()->administrator()->create();
        $editor = AdminUser::factory()->withPermissions(['content.pages.manage'])->create();
        $revisions = app(PageRevisions::class);

        $page = Page::factory()->published()->create();
        $page->syncBlocks('body', 'en', [['type' => 'html', 'data' => ['html' => '<script>old()</script>']]]);
        $withHtml = $revisions->record($page, $admin);

        $page->syncBlocks('body', 'en', [['type' => 'rich_text', 'data' => ['content' => '<p>Clean</p>']]]);
        $revisions->record($page, $admin);

        try {
            $revisions->restore($withHtml, $editor);
            $this->fail('The editor restored an HTML block.');
        } catch (LockedBlocksException) {
            $this->assertSame('rich_text', $page->fresh()->blocksFor('body', 'en')[0]['type']);
        }

        $revisions->restore($withHtml, $admin);
        $this->assertSame('html', $page->fresh()->blocksFor('body', 'en')[0]['type']);
    }

    public function test_signed_order_links_expire(): void
    {
        $order = Order::factory()->create();
        $link = OrderLinks::signedShow($order);

        $this->get($link)->assertOk();

        $this->flushSession();
        $this->travel(OrderLinks::days() + 1)->days();
        $this->get($link)->assertForbidden();
    }

    public function test_wrong_coupon_codes_are_rate_limited(): void
    {
        $product = Product::factory()->active()->create(['stock' => 5]);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1]);

        foreach (range(1, 10) as $attempt) {
            $this->post(route('cart.coupon.store'), ['code' => "GUESS{$attempt}"])->assertSessionHasErrors(['code' => 'This coupon code is not valid.']);
        }

        $this->post(route('cart.coupon.store'), ['code' => 'GUESS11'])->assertSessionHasErrors(['code' => 'Too many coupon codes tried. Please wait a few minutes.']);
    }

    public function test_guests_request_returns_through_the_api_with_their_signed_link(): void
    {
        $product = Product::factory()->active()->create(['stock' => 5]);
        $token = $this->postJson('/api/store/v1/cart/items', ['product_id' => $product->id, 'quantity' => 1])->json('data.token');
        $checkout = $this->withHeader('X-Cart-Token', $token)->postJson('/api/store/v1/checkout', [
            ...collect($this->checkoutData(PaymentMethod::factory()->create(['gateway' => 'bank_transfer'])->id))->except(['website', 'form_started_at'])->all(),
        ])->assertCreated();

        $order = Order::query()->findOrFail($checkout->json('data.id'));
        app(OrderWorkflow::class)->transition($order, PaymentStatus::Paid);
        app(ShipmentService::class)->ship($order);

        $query = (string) parse_url((string) $checkout->json('links.order'), PHP_URL_QUERY);
        $item = $order->items()->sole()->id;

        $this->flushHeaders();
        $this->postJson("/api/store/v1/orders/{$order->id}/returns", ['items' => [$item => 1], 'reason' => 'damaged'])->assertNotFound();
        $this->postJson("/api/store/v1/orders/{$order->id}/returns?{$query}", ['items' => [$item => 1], 'reason' => 'damaged'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'requested');
    }
}
