<?php

namespace PnShop\Promotion\Conditions;

use Filament\Forms\Components\TextInput;
use PnShop\Cart\CartItemDTO;
use PnShop\Promotion\Contracts\ConditionType;
use PnShop\Promotion\Fields;
use PnShop\Promotion\PromotionContext;

/**
 * At least N units in the cart, optionally counting only some products or categories.
 */
class QuantityCondition implements ConditionType
{
    public function key(): string
    {
        return 'quantity';
    }

    public function label(): string
    {
        return 'Number of items is at least';
    }

    public function fields(): array
    {
        return [
            TextInput::make('min')->label('Minimum number of items')->integer()->minValue(1)->required(),
            Fields::products('Count only these products'),
            Fields::categories('Count only these categories'),
        ];
    }

    public function rules(): array
    {
        return ['min' => ['required', 'integer', 'min:1'], ...Fields::scopeRules()];
    }

    public function passes(PromotionContext $context, array $data): bool
    {
        $quantity = $context->items()
            ->filter(fn (CartItemDTO $item) => $context->inScope($item, $data))
            ->sum('quantity');

        return $quantity >= (int) ($data['min'] ?? 1);
    }
}
