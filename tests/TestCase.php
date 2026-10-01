<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PnShop\Security\BotTrap;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * A valid checkout form submission; billing is the same as shipping.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function checkoutData(int $paymentMethodId, array $overrides = []): array
    {
        return array_replace_recursive([
            'email' => 'jane@example.com',
            'shipping' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'line1' => '123 Main St',
                'city' => 'Sofia',
                'postcode' => '1000',
                'country_code' => 'BG',
                'phone' => '0888123456',
            ],
            'billing_same_as_shipping' => true,
            'payment_method_id' => $paymentMethodId,
            ...BotTrap::fields(now()->subMinute()),
        ], $overrides);
    }
}
