<?php

namespace PnShop\Payment\Gateways;

use PnShop\Payment\Models\Payment;
use PnShop\Payment\Models\PaymentMethod;
use PnShop\Settings\SettingDefinition;
use PnShop\Settings\SettingType;

final class BankTransfer extends ManualGateway
{
    public function code(): string
    {
        return 'bank_transfer';
    }

    public function label(): string
    {
        return 'Bank transfer';
    }

    public function settings(): array
    {
        return [
            new SettingDefinition('account_holder', SettingType::String, 'Account holder', rules: ['max:150']),
            new SettingDefinition('iban', SettingType::String, 'IBAN', rules: ['max:42']),
            new SettingDefinition('bic', SettingType::String, 'BIC / SWIFT', rules: ['max:16']),
            new SettingDefinition('bank_name', SettingType::String, 'Bank', rules: ['max:150']),
            new SettingDefinition('instructions', SettingType::Text, 'Instructions for the customer', default: 'Please transfer :amount and use :order as the payment reference.', help: ':amount and :order are replaced with the order total and number.'),
        ];
    }

    public function instructions(Payment $payment, PaymentMethod $method): ?string
    {
        $details = array_filter([
            __('Account holder') => $method->setting('account_holder'),
            'IBAN' => $method->setting('iban'),
            'BIC' => $method->setting('bic'),
            __('Bank') => $method->setting('bank_name'),
        ], fn (mixed $value) => is_string($value) && $value !== '');

        $lines = array_map(fn (string $label, string $value) => "{$label}: {$value}", array_keys($details), $details);

        return trim(implode("\n", [(string) parent::instructions($payment, $method), ...$lines])) ?: null;
    }
}
