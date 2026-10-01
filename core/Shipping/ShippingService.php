<?php

namespace PnShop\Shipping;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\Models\ShippingZone;
use Throwable;

/**
 * Finds the shipping zone for an address and prices its methods.
 */
class ShippingService
{
    /**
     * Whether checkout asks for a shipping method: as soon as the store has one.
     */
    public function isRequired(): bool
    {
        return ShippingMethod::query()->active()->exists();
    }

    public function zoneFor(string $countryCode, ?string $postcode): ?ShippingZone
    {
        return ShippingZone::query()->orderBy('position')->orderBy('id')->get()
            ->first(fn (ShippingZone $zone) => $zone->matches($countryCode, $postcode));
    }

    /**
     * The methods of the matching zone that can deliver the request, cheapest first.
     *
     * @return Collection<int, ShippingQuote>
     */
    public function quotes(ShippingRequest $request): Collection
    {
        $zone = $this->zoneFor($request->countryCode, $request->postcode);

        if ($zone === null) {
            return collect();
        }

        return $zone->methods()->active()->get()
            ->map(fn (ShippingMethod $method) => $this->quoteFor($method, $request, $zone))
            ->filter()
            ->sortBy(fn (ShippingQuote $quote) => $quote->price->getMinorAmount()->toInt())
            ->values();
    }

    /**
     * The price of one method for the request, or null when it can't deliver it
     * (inactive, another zone, carrier not installed or refusing).
     */
    public function quote(ShippingMethod $method, ShippingRequest $request): ?ShippingQuote
    {
        $zone = $this->zoneFor($request->countryCode, $request->postcode);

        return $zone !== null && $method->is_active ? $this->quoteFor($method, $request, $zone) : null;
    }

    private function quoteFor(ShippingMethod $method, ShippingRequest $request, ShippingZone $zone): ?ShippingQuote
    {
        $carrier = $method->carrierInstance();

        if ($carrier === null || $method->shipping_zone_id !== $zone->id) {
            return null;
        }

        try {
            $price = $carrier->quote($request, $method);
        } catch (Throwable $e) {
            Log::warning('Shipping carrier failed to quote.', ['carrier' => $carrier->code(), 'method' => $method->id, 'exception' => $e]);

            return null;
        }

        return $price === null ? null : new ShippingQuote($method, $price);
    }
}
