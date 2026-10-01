<?php

namespace PnShop\Promotion\Conditions;

use Filament\Forms\Components\Select;
use PnShop\Localization\Models\Country;
use PnShop\Promotion\Contracts\ConditionType;
use PnShop\Promotion\PromotionContext;

/**
 * Delivery to one of the countries. Before the customer has entered an address (the cart
 * page) it does not hold.
 */
class ShippingCountryCondition implements ConditionType
{
    public function key(): string
    {
        return 'shipping_country';
    }

    public function label(): string
    {
        return 'Shipping country is';
    }

    public function fields(): array
    {
        return [Select::make('country_codes')->label('Countries')->multiple()->searchable()
            ->options(fn () => Country::query()->where('is_active', true)->get()->mapWithKeys(fn (Country $country) => [$country->code => $country->name()])->all())
            ->required()];
    }

    public function rules(): array
    {
        return ['country_codes' => ['required', 'array', 'min:1'], 'country_codes.*' => ['string', 'size:2']];
    }

    public function passes(PromotionContext $context, array $data): bool
    {
        $country = $context->shippingAddress()?->country_code;

        return $country !== null && in_array(strtoupper($country), array_map('strtoupper', (array) ($data['country_codes'] ?? [])), true);
    }
}
