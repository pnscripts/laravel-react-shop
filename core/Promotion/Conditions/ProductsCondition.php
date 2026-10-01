<?php

namespace PnShop\Promotion\Conditions;

use PnShop\Cart\CartItemDTO;
use PnShop\Promotion\Contracts\ConditionType;
use PnShop\Promotion\Fields;
use PnShop\Promotion\PromotionContext;

class ProductsCondition implements ConditionType
{
    public function key(): string
    {
        return 'products';
    }

    public function label(): string
    {
        return 'Cart contains products';
    }

    public function fields(): array
    {
        return [Fields::products(), Fields::categories()];
    }

    public function rules(): array
    {
        return Fields::scopeRules();
    }

    public function passes(PromotionContext $context, array $data): bool
    {
        if (empty($data['product_ids']) && empty($data['category_ids'])) {
            return false;
        }

        return $context->items()->contains(fn (CartItemDTO $item) => $context->inScope($item, $data));
    }
}
