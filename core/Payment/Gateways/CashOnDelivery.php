<?php

namespace PnShop\Payment\Gateways;

use PnShop\Settings\SettingDefinition;
use PnShop\Settings\SettingType;

final class CashOnDelivery extends ManualGateway
{
    public function code(): string
    {
        return 'cash_on_delivery';
    }

    public function label(): string
    {
        return 'Cash on delivery';
    }

    public function settings(): array
    {
        return [
            new SettingDefinition('instructions', SettingType::Text, 'Instructions for the customer', default: 'Please have :amount ready when your order arrives.', help: ':amount and :order are replaced with the order total and number.'),
        ];
    }
}
