<?php

namespace Tests\Feature\Orders;

use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PnShop\Catalog\Models\Product;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Payment\PaymentState;
use PnShop\Sales\Filament\Resources\Orders\Pages\ViewOrder;
use PnShop\Sales\Models\Order;
use PnShop\Sales\Notifications\OrderConfirmation;
use PnShop\Sales\Notifications\OrderRefunded;
use PnShop\Sales\Notifications\OrderShipped;
use PnShop\Sales\States\FulfillmentStatus;
use PnShop\Sales\States\OrderStatus;
use PnShop\Sales\States\PaymentStatus;
use PnShop\Settings\Settings;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\Models\ShippingZone;
use PnShop\Tax\Models\TaxClass;
use PnShop\Tax\Models\TaxZone;
use Tests\Feature\Admin\AdminTestCase;

/**
 * One order from the cart to a partial refund, through the storefront and the admin.
 */
class OrderLifecycleTest extends AdminTestCase
{
    public function test_an_order_goes_from_checkout_to_refund(): void
    {
        Notification::fake();

        app(Settings::class)->set('tax', ['prices_include_tax' => true, 'store_country' => 'BG']);
        $standard = TaxClass::query()->create(['name' => 'Standard', 'is_default' => true]);
        TaxZone::query()->create(['name' => 'Bulgaria', 'countries' => ['BG']])->rates()->create(['tax_class_id' => $standard->id, 'name' => 'VAT 20%', 'rate' => 20]);

        $courier = ShippingMethod::factory()->for(ShippingZone::factory()->create(['countries' => ['BG']]), 'zone')
            ->create(['name' => 'Courier', 'settings' => ['cost' => '6.00', 'tracking_url' => 'https://track.example/{number}']]);
        $bank = PaymentMethod::factory()->create(['gateway' => 'bank_transfer', 'settings' => ['iban' => 'BG80BNBG96611020345678']]);
        $mug = Product::factory()->active()->create(['title' => 'Mug', 'price' => '12.00', 'sale_price' => null, 'stock' => 10]);

        // Storefront: cart, delivery quote, checkout.
        $this->post(route('cart.store'), ['product_id' => $mug->id, 'quantity' => 3]);
        $this->postJson(route('checkout.quote'), ['country_code' => 'BG', 'postcode' => '1000'])
            ->assertJsonPath('options.0.name', 'Courier')
            ->assertJsonPath('totals.total.amount', '42.00');
        $this->post(route('checkout.store'), $this->checkoutData($bank->id, ['shipping_method_id' => $courier->id]))->assertSessionMissing('error');

        $order = Order::query()->sole();
        $line = $order->items->sole();
        $this->assertSame([OrderStatus::Pending, PaymentStatus::Unpaid, FulfillmentStatus::Unfulfilled], [$order->status, $order->payment_status, $order->fulfillment_status]);
        $this->assertSame('42.00', (string) $order->total->getAmount());
        // VAT included in 36.00 of goods and 6.00 of shipping.
        $this->assertSame(700, collect($order->totals)->firstWhere('code', 'tax')['amount']);
        $this->assertSame(['on_hand' => 10, 'reserved' => 3], $this->level($mug));
        Notification::assertSentOnDemand(OrderConfirmation::class);
        $this->get(route('orders.show', $order))->assertInertia(fn ($page) => $page->where('order.payment_instructions', fn (string $text) => str_contains($text, 'BG80BNBG96611020345678')));

        // Admin: the transfer arrives, the order ships in two parcels.
        $this->actingAsAdministrator();
        $page = Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()]);
        $page->callAction('changePayment', ['state' => 'paid'])->assertHasNoActionErrors();
        $this->assertNotNull($order->fresh()->invoice);
        $this->assertSame(PaymentState::Paid, $order->payments()->sole()->status);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('createShipment', ['quantities' => [$line->id => 2], 'tracking_number' => 'P1'])->assertHasNoActionErrors();
        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('createShipment', ['quantities' => [$line->id => 1], 'tracking_number' => 'P2'])->assertHasNoActionErrors();
        Notification::assertSentOnDemandTimes(OrderShipped::class, 2);
        $this->assertSame(['on_hand' => 7, 'reserved' => 0], $this->level($mug));

        // One mug comes back broken: refund it with the shipping, and restock nothing.
        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('refund', ['quantities' => [$line->id => 1], 'extra' => '6.00', 'restock' => false, 'reason' => 'Broken'])
            ->assertHasNoActionErrors();
        Notification::assertSentOnDemand(OrderRefunded::class);

        $order->refresh();
        $this->assertSame([OrderStatus::Processing, PaymentStatus::PartiallyRefunded, FulfillmentStatus::Fulfilled], [$order->status, $order->payment_status, $order->fulfillment_status]);
        $this->assertSame('18.00', (string) $order->payments()->sole()->refunded_amount->getAmount());
        $this->assertSame(['on_hand' => 7, 'reserved' => 0], $this->level($mug));

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])->callAction('changeStatus', ['state' => 'completed'])->assertHasNoActionErrors();
        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        $this->assertGreaterThanOrEqual(7, $order->history()->count());
    }

    /**
     * @return array{on_hand: int, reserved: int}
     */
    private function level(Product $product): array
    {
        return $product->defaultVariant()->stockLevels()->sole()->only(['on_hand', 'reserved']);
    }
}
