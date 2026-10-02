<?php

namespace PnShop\System;

use Illuminate\Support\Facades\Gate;
use PnShop\Foundation\Extension\Permission;
use PnShop\Foundation\ModuleServiceProvider;
use PnShop\System\Policies\ActivityPolicy;
use Spatie\Activitylog\Models\Activity;

class SystemServiceProvider extends ModuleServiceProvider
{
    protected function permissions(): array
    {
        return [
            new Permission('system.activity.view', 'View the activity log', 'System'),
        ];
    }

    protected function bootModule(): void
    {
        Gate::policy(Activity::class, ActivityPolicy::class);
    }
}
