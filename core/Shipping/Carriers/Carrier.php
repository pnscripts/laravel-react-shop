<?php

namespace PnShop\Shipping\Carriers;

use PnShop\Settings\SettingDefinition;
use PnShop\Settings\SettingType;
use PnShop\Shipping\Contracts\ShippingCarrier;
use PnShop\Shipping\Models\ShippingMethod;

/**
 * Shared behaviour of the built-in carriers: an optional tracking link template.
 */
abstract class Carrier implements ShippingCarrier
{
    /**
     * Carrier-specific settings; the tracking link setting is appended.
     *
     * @return list<SettingDefinition>
     */
    abstract protected function carrierSettings(): array;

    public function settings(): array
    {
        return [
            ...$this->carrierSettings(),
            new SettingDefinition('tracking_url', SettingType::Url, 'Tracking link', help: 'Optional. {number} is replaced with the tracking number, e.g. https://courier.example/track/{number}.', rules: ['max:500']),
        ];
    }

    public function trackingUrl(string $trackingNumber, ShippingMethod $method): ?string
    {
        $template = trim((string) $method->setting('tracking_url'));

        return $template === '' ? null : str_replace('{number}', rawurlencode($trackingNumber), $template);
    }
}
