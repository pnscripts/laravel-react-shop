<?php

namespace PnShop\Admin;

use PnShop\Foundation\ModuleServiceProvider;

class AdminServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->register(AdminPanelProvider::class);
    }
}
