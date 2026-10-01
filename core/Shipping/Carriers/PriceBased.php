<?php

namespace PnShop\Shipping\Carriers;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use PnShop\Settings\SettingDefinition;
use PnShop\Settings\SettingType;
use PnShop\Shipping\Models\ShippingMethod;
use PnShop\Shipping\ShippingRequest;

final class PriceBased extends Carrier
{
    public function code(): string
    {
        return 'price_based';
    }

    public function label(): string
    {
        return 'By order subtotal';
    }

    protected function carrierSettings(): array
    {
        return [
            new SettingDefinition('rates', SettingType::Text, 'Rates (subtotal from: price)', default: "0: 7.00\n50: 4.00\n100: 0", required: true, help: 'One line per band: from that subtotal the price applies.', rules: [new RateTableRule]),
        ];
    }

    public function quote(ShippingRequest $request, ShippingMethod $method): ?Money
    {
        $price = RateTable::from((string) $method->setting('rates'), (float) (string) $request->subtotal->getAmount());

        return $price === null ? null : Money::of($price, $request->currency(), roundingMode: RoundingMode::HalfUp);
    }
}
