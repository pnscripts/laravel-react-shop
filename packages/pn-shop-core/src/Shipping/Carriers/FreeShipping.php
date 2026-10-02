<?php

namespace PnShop\Shipping\Carriers;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use PnShop\Settings\SettingDefinition;
use PnShop\Settings\SettingType;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\ShippingRequest;

final class FreeShipping extends Carrier
{
    public function code(): string
    {
        return 'free_shipping';
    }

    public function label(): string
    {
        return 'Free shipping';
    }

    protected function carrierSettings(): array
    {
        return [
            new SettingDefinition('min_subtotal', SettingType::Decimal, 'Minimum order subtotal', help: 'Offered only from this subtotal. Leave empty to always offer it.', rules: ['min:0']),
        ];
    }

    public function quote(ShippingRequest $request, ShippingMethod $method): ?Money
    {
        $minimum = $method->setting('min_subtotal');

        if ($minimum !== null && $minimum !== '' && $request->subtotal->isLessThan(Money::of((string) $minimum, $request->currency(), roundingMode: RoundingMode::HalfUp))) {
            return null;
        }

        return Money::zero($request->currency());
    }
}
