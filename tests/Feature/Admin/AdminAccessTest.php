<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use PnShop\Acl\Models\AdminUser;

class AdminAccessTest extends AdminTestCase
{
    public function test_guests_are_sent_to_the_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/products')->assertRedirect('/admin/login');
    }

    public function test_customers_cannot_use_the_admin(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_inactive_staff_cannot_use_the_admin(): void
    {
        $this->actingAs(AdminUser::factory()->administrator()->inactive()->create(), 'admin');

        $this->get('/admin')->assertForbidden();
    }

    public function test_administrators_reach_every_section(): void
    {
        $this->actingAsAdministrator();

        foreach (['/admin', '/admin/products', '/admin/orders', '/admin/admin-users', '/admin/roles', '/admin/settings', '/admin/activities'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_staff_only_reach_sections_their_role_allows(): void
    {
        $this->actingAsStaff(role: 'catalog-manager');

        $this->get('/admin/products')->assertOk();
        $this->get('/admin/orders')->assertForbidden();
        $this->get('/admin/admin-users')->assertForbidden();
        $this->get('/admin/settings')->assertForbidden();
    }
}
