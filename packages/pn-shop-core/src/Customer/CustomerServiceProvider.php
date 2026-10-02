<?php

namespace PnShop\Customer;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use PnShop\Customer\Models\CustomerAddress;
use PnShop\Customer\Models\CustomerGroup;
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
            'customer' => User::class,
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
