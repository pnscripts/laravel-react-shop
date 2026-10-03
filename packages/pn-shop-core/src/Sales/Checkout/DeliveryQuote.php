<?php

namespace PnShop\Sales\Checkout;

use PnShop\Cart\ShoppingCartService;
use PnShop\Customer\Models\User;
use PnShop\Customer\PostalAddress;
use PnShop\Shipping\ShippingQuote;
use PnShop\Shipping\ShippingRequest;
use PnShop\Shipping\ShippingService;

/**
 * The delivery options and totals for the address a customer is entering at checkout,
 * shared by the storefront and the Store API.
 */
class DeliveryQuote
{
    /** @var array<string, list<string>> */
    public const RULES = [
        'country_code' => ['required', 'string', 'size:2'],
        'postcode' => ['nullable', 'string', 'max:32'],
        'shipping_method_id' => ['nullable', 'integer'],
    ];

    public function __construct(private ShoppingCartService $cart, private ShippingService $shipping) {}

    /**
     * @param  array<string, mixed>  $data  validated with RULES
     * @return array{options: list<array<string, mixed>>, selected: int|null, totals: array<string, mixed>}
     */
    public function for(array $data, ?User $customer): array
    {
        $address = PostalAddress::fromArray($data);
        $quotes = $this->shipping->quotes(new ShippingRequest($this->cart->getCartItems(), $this->cart->getTotalPrice(), $address->country_code, $address->postcode, $customer));

        $selected = $quotes->first(fn (ShippingQuote $quote) => $quote->method->id === (int) ($data['shipping_method_id'] ?? 0)) ?? $quotes->first();

        return [
            'options' => array_values($quotes->map(fn (ShippingQuote $quote) => $quote->toArray())->all()),
            'selected' => $selected?->method->id,
            'totals' => $this->cart->totals([
                'shipping_address' => $address,
                'shipping_method' => $selected?->method,
                'user' => $customer,
            ])->toArray(),
        ];
    }
}
