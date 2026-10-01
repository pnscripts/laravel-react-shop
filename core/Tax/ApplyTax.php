<?php

namespace PnShop\Tax;

use Brick\Money\Money;
use Closure;
use PnShop\Cart\CartItemDTO;
use PnShop\Cart\Totals\CartTotals;
use PnShop\Cart\Totals\TotalLine;
use PnShop\Customer\PostalAddress;
use PnShop\Settings\Settings;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Tax\Contracts\TaxProvider;

/**
 * cart.totals stage: tax on the lines and the shipping, per rate. With tax-inclusive
 * prices the tax is shown as included; otherwise it is added to the total.
 *
 * The address taxed is the shipping or billing address (setting tax.based_on), falling
 * back to the store's country before the customer has entered one.
 */
class ApplyTax
{
    public const PRIORITY = 400;

    public function __construct(
        private TaxProvider $provider,
        private Settings $settings,
    ) {}

    public function handle(CartTotals $totals, Closure $next): mixed
    {
        [$country, $postcode] = $this->destination($totals);

        if ($country === null) {
            return $next($totals);
        }

        // Tax is due on what is paid: amounts after discounts.
        $lines = $totals->items
            ->map(fn (CartItemDTO $item) => new TaxableLine('item:'.$item->variant_id, $this->net($item->getTotalPrice(), $totals->discountOn('item:'.$item->variant_id)), $item->taxClassId))
            ->values()
            ->all();

        $shipping = $totals->line('shipping');
        $method = $totals->context['shipping_method'] ?? null;

        if ($shipping !== null) {
            $lines[] = new TaxableLine('shipping', $this->net($shipping->amount, $totals->discountOn('shipping')), $method instanceof ShippingMethod ? $method->tax_class_id : null);
        }

        $inclusive = (bool) $this->settings->get('tax.prices_include_tax');
        $result = $this->provider->calculate(new TaxRequest(array_values($lines), $totals->currency(), $country, $postcode, $inclusive, $totals->context['user'] ?? null));

        $totals->meta['tax'] = $result;

        $index = 0;
        foreach ($result->byRate() as $name => $amount) {
            $totals->add(new TotalLine('tax'.($index++ === 0 ? '' : ":{$index}"), $name, $amount, included: $inclusive));
        }

        return $next($totals);
    }

    private function net(Money $amount, Money $discount): Money
    {
        $net = $amount->minus($discount);

        return $net->isNegative() ? Money::zero($amount->getCurrency()) : $net;
    }

    /**
     * @return array{string|null, string|null}
     */
    private function destination(CartTotals $totals): array
    {
        $basedOn = $this->settings->get('tax.based_on');
        $address = match ($basedOn) {
            'billing' => $totals->context['billing_address'] ?? $totals->context['shipping_address'] ?? null,
            'store' => null,
            default => $totals->context['shipping_address'] ?? null,
        };

        if ($address instanceof PostalAddress && $address->country_code !== '') {
            return [$address->country_code, $address->postcode];
        }

        $storeCountry = strtoupper(trim((string) $this->settings->get('tax.store_country')));

        return [$storeCountry === '' ? null : $storeCountry, null];
    }
}
