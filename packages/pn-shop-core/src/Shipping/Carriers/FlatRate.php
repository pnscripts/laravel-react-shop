<?php

namespace PnShop\Shipping\Carriers;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use PnShop\Settings\SettingDefinition;
use PnShop\Settings\SettingType;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\ShippingRequest;

final class FlatRate extends Carrier
{
    public function code(): string
    {
        return 'flat_rate';
    }

    public function label(): string
    {
        return 'Flat rate';
    }

    protected function carrierSettings(): array
    {
        return [
            new SettingDefinition('cost', SettingType::Decimal, 'Price', default: '5.00', required: true, rules: ['min:0']),
            new SettingDefinition('per', SettingType::Select, 'Charged', default: 'order', required: true, options: ['order' => 'Once per order', 'item' => 'Per item']),
        ];
    }

    public function quote(ShippingRequest $request, ShippingMethod $method): Money
    {
        $cost = Money::of((string) $method->setting('cost'), $request->currency(), roundingMode: RoundingMode::HalfUp);

        return $method->setting('per') === 'item' ? $cost->multipliedBy($request->quantity()) : $cost;
    }
}
