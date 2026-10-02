<?php

namespace PnShop\Shipping\Carriers;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use PnShop\Settings\SettingDefinition;
use PnShop\Settings\SettingType;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\ShippingRequest;

final class WeightBased extends Carrier
{
    public function code(): string
    {
        return 'weight_based';
    }

    public function label(): string
    {
        return 'By weight';
    }

    protected function carrierSettings(): array
    {
        return [
            new SettingDefinition('rates', SettingType::Text, 'Rates (grams: price)', default: "1000: 5.00\n5000: 9.00\n20000: 15.00", required: true, help: 'One line per weight band: up to that many grams costs that price. Heavier orders are not offered this method.', rules: [new RateTableRule]),
        ];
    }

    public function quote(ShippingRequest $request, ShippingMethod $method): ?Money
    {
        $price = RateTable::upTo((string) $method->setting('rates'), $request->weight());

        return $price === null ? null : Money::of($price, $request->currency(), roundingMode: RoundingMode::HalfUp);
    }
}
