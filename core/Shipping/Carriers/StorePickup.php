<?php

namespace PnShop\Shipping\Carriers;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use PnShop\Settings\SettingDefinition;
use PnShop\Settings\SettingType;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\ShippingRequest;

final class StorePickup extends Carrier
{
    public function code(): string
    {
        return 'pickup';
    }

    public function label(): string
    {
        return 'Pickup';
    }

    protected function carrierSettings(): array
    {
        return [
            new SettingDefinition('cost', SettingType::Decimal, 'Price', default: '0', rules: ['min:0']),
            new SettingDefinition('location', SettingType::Text, 'Pickup address and hours', help: 'Shown to the customer at checkout and on the order page.'),
        ];
    }

    public function quote(ShippingRequest $request, ShippingMethod $method): Money
    {
        return Money::of((string) ($method->setting('cost') ?: '0'), $request->currency(), roundingMode: RoundingMode::HalfUp);
    }

    public function trackingUrl(string $trackingNumber, ShippingMethod $method): ?string
    {
        return null;
    }
}
