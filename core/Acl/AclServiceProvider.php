<?php

namespace PnShop\Acl;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use PnShop\Acl\Console\CreateAdminCommand;
use PnShop\Acl\Console\SyncPermissionsCommand;
use PnShop\Acl\Models\AdminUser;
use PnShop\Acl\Policies\AdminUserPolicy;
use PnShop\Acl\Policies\RolePolicy;
use PnShop\Foundation\Extension\Permission;
use PnShop\Foundation\ModuleServiceProvider;
use Spatie\Permission\Models\Role;

class AclServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionSynchronizer::class);

        Relation::morphMap(['admin_user' => AdminUser::class]);
    }

    protected function permissions(): array
    {
        return [
            new Permission('system.admin_users.manage', 'Manage admin users', 'System'),
            new Permission('system.roles.manage', 'Manage roles and permissions', 'System'),
        ];
    }

    protected function bootModule(): void
    {
        Gate::policy(AdminUser::class, AdminUserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        // Administrators hold every permission, including ones registered later. Only permission
        // keys (always dotted) are granted here; policy abilities such as "delete" still run their
        // own rules (e.g. nobody may delete the last administrator).
        Gate::before(fn (mixed $user, string $ability) => $user instanceof AdminUser
            && str_contains($ability, '.')
            && $user->isAdministrator() ? true : null);

        // Keep permissions in step with the code after every migrate (install, update, tests).
        Event::listen(MigrationsEnded::class, function (): void {
            if (Schema::hasTable('permissions')) {
                $this->app->make(PermissionSynchronizer::class)->sync();
            }
        });

        if ($this->app->runningInConsole()) {
            $this->commands([CreateAdminCommand::class, SyncPermissionsCommand::class]);
        }
    }
}
