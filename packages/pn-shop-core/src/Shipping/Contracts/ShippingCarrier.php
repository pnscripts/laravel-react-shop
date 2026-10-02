<?php

namespace PnShop\Shipping\Contracts;

use Brick\Money\Money;
use PnShop\Settings\SettingDefinition;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\ShippingRequest;

/**
 * A way to price (and later book) delivery: flat rate, free shipping, pickup, weight
 * or price tables, or a courier's API.
 *
 * Merchants create shipping methods in a shipping zone that use a carrier with their own
 * settings. Carriers are registered with ShippingCarrierManager by core and extensions;
 * PnShop\Shipping\Testing\ShippingCarrierContractTests checks an implementation.
 */
interface ShippingCarrier
{
    /** Stable identifier stored on shipping methods, e.g. "flat_rate". */
    public function code(): string;

    /** Name shown to staff when choosing a carrier. */
    public function label(): string;

    /**
     * @return list<SettingDefinition>
     */
    public function settings(): array;

    /**
     * The price of delivering this request with the method, or null when the method
     * cannot deliver it (too heavy, below a threshold, ...).
     */
    public function quote(ShippingRequest $request, ShippingMethod $method): ?Money;

    /** A link where the customer can follow a parcel, when the carrier has one. */
    public function trackingUrl(string $trackingNumber, ShippingMethod $method): ?string;
}
