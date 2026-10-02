<?php

namespace PnShop\Customer\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use PnShop\Customer\Models\CustomerAddress;
use PnShop\Customer\Models\User;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    /** @var class-string<CustomerAddress> */
    protected $model = CustomerAddress::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'line1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postcode' => fake()->postcode(),
            'country_code' => 'BG',
            'phone' => fake()->phoneNumber(),
        ];
    }
}
