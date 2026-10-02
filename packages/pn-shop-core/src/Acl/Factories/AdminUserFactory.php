<?php

namespace PnShop\Acl\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use PnShop\Acl\Models\AdminUser;

/**
 * @extends Factory<AdminUser>
 */
class AdminUserFactory extends Factory
{
    /** @var class-string<AdminUser> */
    protected $model = AdminUser::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'is_active' => true,
        ];
    }

    public function administrator(): static
    {
        return $this->afterCreating(fn (AdminUser $admin) => $admin->assignRole(AdminUser::ADMINISTRATOR_ROLE));
    }

    /**
     * @param  list<string>  $permissions
     */
    public function withPermissions(array $permissions): static
    {
        return $this->afterCreating(fn (AdminUser $admin) => $admin->givePermissionTo($permissions));
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
