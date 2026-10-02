<?php

namespace PnShop\Customer;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use PnShop\Customer\Models\CustomerAddress;
use PnShop\Customer\Models\CustomerGroup;
use PnShop\Customer\Models\User;
use PnShop\Customer\Policies\CustomerGroupPolicy;
use PnShop\Customer\Policies\CustomerPolicy;
use PnShop\Foundation\Extension\Permission;
use PnShop\Foundation\ModuleServiceProvider;

/**
 * Customer accounts (the `users` table), customer groups and address books.
 */
class CustomerServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        Relation::morphMap([
            // The shop's configured customer model (App\Models\User), so stored morph types
            // resolve to the class the shop actually uses.
            'customer' => config('auth.providers.users.model', User::class),
            'customer_address' => CustomerAddress::class,
        ]);
    }

    protected function permissions(): array
    {
        return [
            new Permission('customers.view', 'View customers', 'Customers'),
            new Permission('customers.manage', 'Edit customers and customer groups', 'Customers'),
        ];
    }

    protected function bootModule(): void
    {
        Gate::policy(User::class, CustomerPolicy::class);
        Gate::policy(CustomerGroup::class, CustomerGroupPolicy::class);
    }
}
