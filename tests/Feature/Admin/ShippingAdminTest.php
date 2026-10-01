<?php

namespace Tests\Feature\Admin;

use Livewire\Livewire;
use PnShop\Catalog\Models\Product;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Sales\Filament\Resources\Orders\Pages\ViewOrder;
use PnShop\Sales\Models\Order;
use PnShop\Sales\States\FulfillmentStatus;
use PnShop\Shipping\Filament\RelationManagers\ShipmentsRelationManager;
use PnShop\Shipping\Filament\Resources\ShippingMethods\Pages\CreateShippingMethod;
use PnShop\Shipping\Filament\Resources\ShippingZones\Pages\ManageShippingZones;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\Models\ShippingZone;

class ShippingAdminTest extends AdminTestCase
{
    public function test_zones_and_methods_are_set_up_in_the_admin(): void
    {
        $this->actingAsAdministrator();

        Livewire::test(ManageShippingZones::class)
            ->callAction('create', ['name' => 'Bulgaria', 'countries' => ['BG'], 'postcodes' => ['1*']])
            ->assertHasNoActionErrors();

        $zone = ShippingZone::query()->sole();
        $this->assertSame(['BG'], $zone->countries);

        Livewire::test(CreateShippingMethod::class)
            ->fillForm(['name' => 'Speedy', 'shipping_zone_id' => $zone->id, 'carrier' => 'weight_based'])
            ->assertSchemaComponentExists('settings.rates', 'form')
            ->fillForm(['settings' => ['rates' => "2000: 6.50\n10000: 12"]])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame("2000: 6.50\n10000: 12", ShippingMethod::query()->sole()->setting('rates'));
    }

    public function test_rate_tables_are_validated(): void
    {
        $this->actingAsAdministrator();
        $zone = ShippingZone::factory()->create();

        Livewire::test(CreateShippingMethod::class)
            ->fillForm(['name' => 'Bad', 'shipping_zone_id' => $zone->id, 'carrier' => 'price_based'])
            ->fillForm(['settings' => ['rates' => "0: 5\nfifty: 3"]])
            ->call('create')
            ->assertHasFormErrors(['settings.rates']);
    }

    public function test_staff_create_a_shipment_from_the_order(): void
    {
        // The customer checks out before staff sign in (signing in switches the default guard).
        $product = Product::factory()->active()->create(['stock' => 4]);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->post(route('checkout.store'), $this->checkoutData(PaymentMethod::factory()->create(['gateway' => 'cash_on_delivery'])->id))->assertSessionMissing('error');
        $order = Order::query()->sole();
        $line = $order->items->sole();

        $this->actingAsAdministrator();

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('createShipment', ['quantities' => [$line->id => 1], 'tracking_number' => 'TR-1'])
            ->assertHasNoActionErrors();

        $this->assertSame(FulfillmentStatus::PartiallyFulfilled, $order->fresh()->fulfillment_status);

        Livewire::test(ShipmentsRelationManager::class, ['ownerRecord' => $order->fresh(), 'pageClass' => ViewOrder::class])
            ->assertSee('TR-1')
            ->assertSee('1 × '.$line->product_title);
    }
}
