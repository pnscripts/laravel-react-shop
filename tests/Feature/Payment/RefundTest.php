<?php

namespace Tests\Feature\Payment;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Catalog\Models\Product;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Payment\PaymentState;
use PnShop\Payment\RefundService;
use PnShop\Sales\Exceptions\OrderException;
use PnShop\Sales\Models\Order;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\FulfillmentStatus;
use PnShop\Sales\States\PaymentStatus;
use PnShop\Settings\Settings;
use PnShop\Shipping\ShipmentService;
use PnShop\Tax\Models\TaxClass;
use PnShop\Tax\Models\TaxZone;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use RefreshDatabase;

    private Product $mug;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mug = Product::factory()->active()->create(['price' => '10.00', 'sale_price' => null, 'stock' => 10]);
    }

    public function test_unshipped_units_are_cancelled_and_released_when_refunded(): void
    {
        $order = $this->paidOrder(4);
        $line = $order->items->sole();

        $refund = app(RefundService::class)->refund($order, [$line->id => 1], reason: 'Changed mind');

        $this->assertSame('10.00', (string) $refund->amount->getAmount());
        $this->assertSame(PaymentStatus::PartiallyRefunded, $order->fresh()->payment_status);
        $this->assertSame(PaymentState::PartiallyRefunded, $order->payments()->sole()->status);
        $this->assertSame(['on_hand' => 10, 'reserved' => 3], $this->level());

        // Shipping the rest takes only the three kept units and completes the order.
        app(ShipmentService::class)->ship($order->fresh());

        $this->assertSame(FulfillmentStatus::Fulfilled, $order->fresh()->fulfillment_status);
        $this->assertSame(['on_hand' => 7, 'reserved' => 0], $this->level());
    }

    public function test_returned_units_go_back_on_the_shelf_when_restocking(): void
    {
        $order = $this->paidOrder(2);
        app(ShipmentService::class)->ship($order);
        $line = $order->fresh()->items->sole();

        app(RefundService::class)->refund($order->fresh(), [$line->id => 1], restock: true);
        $this->assertSame(['on_hand' => 9, 'reserved' => 0], $this->level());

        app(RefundService::class)->refund($order->fresh(), [$line->id => 1], restock: false);
        $this->assertSame(['on_hand' => 9, 'reserved' => 0], $this->level());

        $order->refresh();
        $this->assertSame(PaymentStatus::Refunded, $order->payment_status);
        $this->assertSame('20.00', (string) $order->payments()->sole()->refunded_amount->getAmount());
        $this->assertSame(PaymentState::Refunded, $order->payments()->sole()->status);
        $this->assertSame(2, $order->refunds()->count());
    }

    public function test_refunds_cannot_exceed_what_was_paid_or_bought(): void
    {
        $order = $this->paidOrder(1);
        $line = $order->items->sole();

        foreach ([[[$line->id => 2], null], [[], Money::of('16.00', 'USD')], [[], null]] as [$quantities, $extra]) {
            try {
                app(RefundService::class)->refund($order, $quantities, $extra);
                $this->fail('The refund should have been refused.');
            } catch (OrderException) {
                //
            }
        }

        $this->assertSame(0, $order->refunds()->count());
    }

    public function test_unpaid_orders_cannot_be_refunded(): void
    {
        $order = $this->paidOrder(1, pay: false);

        $this->expectException(OrderException::class);

        app(RefundService::class)->refund($order, [$order->items->sole()->id => 1]);
    }

    public function test_tax_added_on_top_is_refunded_with_the_line(): void
    {
        app(Settings::class)->set('tax', ['prices_include_tax' => false]);
        $class = TaxClass::query()->create(['name' => 'Standard', 'is_default' => true]);
        TaxZone::query()->create(['name' => 'BG', 'countries' => ['BG']])->rates()->create(['tax_class_id' => $class->id, 'name' => 'VAT 20%', 'rate' => 20]);

        $order = $this->paidOrder(2);

        $refund = app(RefundService::class)->refund($order, [$order->items->sole()->id => 1]);

        $this->assertSame('12.00', (string) $refund->amount->getAmount());
    }

    private function paidOrder(int $quantity, bool $pay = true): Order
    {
        $this->post(route('cart.store'), ['product_id' => $this->mug->id, 'quantity' => $quantity]);
        $this->post(route('checkout.store'), $this->checkoutData(PaymentMethod::factory()->create(['gateway' => 'bank_transfer'])->id))->assertSessionMissing('error');

        $order = Order::query()->latest('id')->firstOrFail();

        if ($pay) {
            app(OrderWorkflow::class)->transition($order, PaymentStatus::Paid);
        }

        return $order->fresh(['items']);
    }

    /**
     * @return array{on_hand: int, reserved: int}
     */
    private function level(): array
    {
        return $this->mug->defaultVariant()->stockLevels()->sole()->only(['on_hand', 'reserved']);
    }
}
