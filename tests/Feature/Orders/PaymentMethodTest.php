<?php

namespace Tests\Feature\Orders;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    /** Test payment method creation. */
    public function test_payment_method_can_be_created(): void
    {
        // Act
        $method = PaymentMethod::factory()->create([
            'name' => 'Cash on Delivery',
            'type' => 'manual',
        ]);

        // Assert
        $this->assertDatabaseHas('payment_methods', [
            'id' => $method->id,
            'name' => 'Cash on Delivery',
            'type' => 'manual',
        ]);
    }

    /** Test soft delete on payment method. */
    public function test_payment_method_can_be_soft_deleted(): void
    {
        // Arrange
        $method = PaymentMethod::factory()->create();

        // Act
        $method->delete();

        // Assert
        $this->assertSoftDeleted($method);
    }
}
