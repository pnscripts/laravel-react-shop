<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use PnShop\Customer\PostalAddress;

class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $billing = array_map(
            fn (array $rules) => ['exclude_if:billing_same_as_shipping,true', ...$rules],
            PostalAddress::rules('billing.'),
        );

        return [
            'email' => ['required', 'email', 'max:255'],
            ...PostalAddress::rules('shipping.'),
            // Couriers need a phone number for the delivery.
            'shipping.phone' => ['required', 'string', 'max:50'],
            'billing_same_as_shipping' => ['boolean'],
            ...$billing,
            'save_address' => ['boolean'],
            'shipping_method_id' => ['nullable', 'integer'],
            'payment_method_id' => [
                'required',
                'integer',
                Rule::exists('payment_methods', 'id')->where('is_active', true),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $names = [
            'first_name' => __('first name'),
            'last_name' => __('last name'),
            'company' => __('company'),
            'line1' => __('street address'),
            'line2' => __('apartment, floor, etc.'),
            'city' => __('city'),
            'postcode' => __('postcode'),
            'region' => __('region'),
            'country_code' => __('country'),
            'phone' => __('phone'),
        ];

        $attributes = [];

        foreach (['shipping', 'billing'] as $type) {
            foreach ($names as $field => $name) {
                $attributes["{$type}.{$field}"] = $name;
            }
        }

        return $attributes;
    }
}
