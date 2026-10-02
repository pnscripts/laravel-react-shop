<?php

namespace PnShop\Promotion\Actions;

use PnShop\Promotion\Contracts\ActionType;
use PnShop\Promotion\Discounts;
use PnShop\Promotion\PromotionContext;

class FreeShippingAction implements ActionType
{
    public function key(): string
    {
        return 'free_shipping';
    }

    public function label(): string
    {
        return 'Free shipping';
    }

    public function fields(): array
    {
        return [];
    }

    public function rules(): array
    {
        return [];
    }

    public function apply(PromotionContext $context, array $data, Discounts $discounts): void
    {
        $discounts->freeShipping();
    }
}
