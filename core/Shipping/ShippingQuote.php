<?php

namespace PnShop\Shipping;

use Brick\Money\Money;
use PnShop\Money\MoneyPresenter;
use PnShop\Shipping\Models\ShippingMethod;

/**
 * A shipping method that can deliver a request, with its price.
 */
final readonly class ShippingQuote
{
    public function __construct(public ShippingMethod $method, public Money $price) {}

    /**
     * @return array{id: int, name: string, description: string|null, price: array<string, mixed>|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->method->id,
            'name' => $this->method->name,
            'description' => $this->method->description,
            'price' => MoneyPresenter::present($this->price),
        ];
    }
}
