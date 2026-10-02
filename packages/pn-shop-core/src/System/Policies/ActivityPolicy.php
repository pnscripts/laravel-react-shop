<?php

namespace PnShop\System\Policies;

use PnShop\Acl\Models\AdminUser;
use Spatie\Activitylog\Models\Activity;

/**
 * The activity log is read-only.
 */
class ActivityPolicy
{
    public function viewAny(AdminUser $admin): bool
    {
        return $admin->can('system.activity.view');
    }

    public function view(AdminUser $admin, Activity $activity): bool
    {
        return $admin->can('system.activity.view');
    }
}
