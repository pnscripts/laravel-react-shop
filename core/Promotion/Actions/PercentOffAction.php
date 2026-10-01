<?php

namespace PnShop\Promotion\Actions;

use Filament\Forms\Components\TextInput;
use PnShop\Cart\CartItemDTO;
use PnShop\Promotion\Contracts\ActionType;
use PnShop\Promotion\Discounts;
use PnShop\Promotion\Fields;
use PnShop\Promotion\PromotionContext;

/**
 * A percentage off the matching lines (all lines when no products or categories are chosen).
 */
class PercentOffAction implements ActionType
{
    public function key(): string
    {
        return 'percent_off';
    }

    public function label(): string
    {
        return 'Percentage off';
    }

    public function fields(): array
    {
        return [
            TextInput::make('percent')->label('Percent off')->numeric()->minValue(0.01)->maxValue(100)->suffix('%')->required(),
            Fields::products('Only these products'),
            Fields::categories('Only these categories'),
        ];
    }

    public function rules(): array
    {
        return ['percent' => ['required', 'numeric', 'min:0.01', 'max:100'], ...Fields::scopeRules()];
    }

    public function apply(PromotionContext $context, array $data, Discounts $discounts): void
    {
        $percent = (float) ($data['percent'] ?? 0);

        $context->items()
            ->filter(fn (CartItemDTO $item) => $context->inScope($item, $data))
            ->each(fn (CartItemDTO $item) => $discounts->percentOff($item, $percent));
    }
}
