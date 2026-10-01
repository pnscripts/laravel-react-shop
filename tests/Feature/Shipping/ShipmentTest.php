<?php

namespace Tests\Feature\Shipping;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PnShop\Catalog\Models\Product;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Sales\Exceptions\OrderException;
use PnShop\Sales\Models\Order;
use PnShop\Sales\OrderWorkflow;
use PnShop\Sales\States\FulfillmentStatus;
use PnShop\Sales\States\OrderStatus;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\Models\ShippingZone;
use PnShop\Shipping\ShipmentService;
use Tests\TestCase;

class ShipmentTest extends TestCase
{
    use RefreshDatabase;

    private Product $mug;

    private Product $lamp;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $zone = ShippingZone::factory()->create(['countries' => ['BG']]);
        $courier = ShippingMethod::factory()->for($zone, 'zone')->create(['settings' => ['cost' => '5', 'tracking_url' => 'https://track.example/{number}']]);
        $payment = PaymentMethod::factory()->create(['gateway' => 'cash_on_delivery']);

        $this->mug = Product::factory()->active()->create(['title' => 'Mug', 'stock' => 10]);
        $this->lamp = Product::factory()->active()->create(['title' => 'Lamp', 'stock' => 5]);

        $this->post(route('cart.store'), ['product_id' => $this->mug->id, 'quantity' => 3]);
        $this->post(route('cart.store'), ['product_id' => $this->lamp->id, 'quantity' => 1]);
        $this->post(route('checkout.store'), $this->checkoutData($payment->id, ['shipping_method_id' => $courier->id]))->assertSessionMissing('error');

        $this->order = Order::query()->sole();
    }

    public function test_a_partial_shipment_takes_only_its_lines_off_the_shelf(): void
    {
        $mugLine = $this->order->items->firstWhere('product_id', $this->mug->id);

        $shipment = app(ShipmentService::class)->ship($this->order, [$mugLine->id => 2], 'AB 123');

        $this->assertSame('https://track.example/AB%20123', $shipment->tracking_url);
        $this->assertSame(FulfillmentStatus::PartiallyFulfilled, $this->order->fresh()->fulfillment_status);
        $this->assertSame(['on_hand' => 8, 'reserved' => 1], $this->mug->defaultVariant()->stockLevels()->sole()->only(['on_hand', 'reserved']));
        $this->assertSame(['on_hand' => 5, 'reserved' => 1], $this->lamp->defaultVariant()->stockLevels()->sole()->only(['on_hand', 'reserved']));

        // The rest ships in a second parcel; nothing is taken twice.
        app(ShipmentService::class)->ship($this->order);

        $this->assertSame(FulfillmentStatus::Fulfilled, $this->order->fresh()->fulfillment_status);
        $this->assertSame(['on_hand' => 7, 'reserved' => 0], $this->mug->defaultVariant()->stockLevels()->sole()->only(['on_hand', 'reserved']));
        $this->assertSame(['on_hand' => 4, 'reserved' => 0], $this->lamp->defaultVariant()->stockLevels()->sole()->only(['on_hand', 'reserved']));
        $this->assertSame(2, $this->order->shipments()->count());
    }

    public function test_more_than_is_left_cannot_be_shipped(): void
    {
        $mugLine = $this->order->items->firstWhere('product_id', $this->mug->id);

        $this->expectException(OrderException::class);

        app(ShipmentService::class)->ship($this->order, [$mugLine->id => 4]);
    }

    public function test_cancelling_after_a_partial_shipment_returns_the_shipped_units_and_releases_the_rest(): void
    {
        $mugLine = $this->order->items->firstWhere('product_id', $this->mug->id);
        app(ShipmentService::class)->ship($this->order, [$mugLine->id => 2]);

        app(OrderWorkflow::class)->transition($this->order, OrderStatus::Cancelled);

        $this->assertSame(['on_hand' => 10, 'reserved' => 0], $this->mug->defaultVariant()->stockLevels()->sole()->only(['on_hand', 'reserved']));
        $this->assertSame(['on_hand' => 5, 'reserved' => 0], $this->lamp->defaultVariant()->stockLevels()->sole()->only(['on_hand', 'reserved']));
    }

    public function test_cancelled_orders_cannot_be_shipped(): void
    {
        app(OrderWorkflow::class)->transition($this->order, OrderStatus::Cancelled);

        $this->expectException(OrderException::class);

        app(ShipmentService::class)->ship($this->order);
    }

    public function test_customers_see_their_parcels_and_tracking_links(): void
    {
        app(ShipmentService::class)->ship($this->order, [], 'ZX9');

        $this->get(route('orders.show', $this->order))->assertInertia(fn ($page) => $page
            ->where('order.shipping_method', 'Courier')
            ->where('order.shipments.0.tracking_url', 'https://track.example/ZX9')
            ->where('order.shipments.0.items', 4)
            ->where('order.fulfillment_status', 'Shipped')
        );
    }
}
