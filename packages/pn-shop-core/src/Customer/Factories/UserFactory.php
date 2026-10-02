<?php

namespace PnShop\Customer\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PnShop\Customer\Models\User;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The shop's configured customer model (App\Models\User by default).
     *
     * @return class-string<User>
     */
    public function modelName(): string
    {
        /** @var class-string<User> $model */
        $model = config('auth.providers.users.model', User::class);

        return $model;
    }

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
