<?php

namespace PnShop\Promotion\Actions;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Filament\Forms\Components\TextInput;
use PnShop\Cart\CartItemDTO;
use PnShop\Promotion\Contracts\ActionType;
use PnShop\Promotion\Discounts;
use PnShop\Promotion\Fields;
use PnShop\Promotion\PromotionContext;

/**
 * "Buy 2, get 1 free": in every group of buy + get matching units, the get cheapest units
 * are discounted (100% = free).
 */
class BuyXGetYAction implements ActionType
{
    public function key(): string
    {
        return 'buy_x_get_y';
    }

    public function label(): string
    {
        return 'Buy X, get Y discounted';
    }

    public function fields(): array
    {
        return [
            TextInput::make('buy')->label('Buy')->integer()->minValue(1)->required(),
            TextInput::make('get')->label('Get')->integer()->minValue(1)->required(),
            TextInput::make('percent')->label('Discount on those')->numeric()->minValue(0.01)->maxValue(100)->default(100)->suffix('%')->required(),
            Fields::products('Only these products'),
            Fields::categories('Only these categories'),
        ];
    }

    public function rules(): array
    {
        return [
            'buy' => ['required', 'integer', 'min:1'],
            'get' => ['required', 'integer', 'min:1'],
            'percent' => ['required', 'numeric', 'min:0.01', 'max:100'],
            ...Fields::scopeRules(),
        ];
    }

    public function apply(PromotionContext $context, array $data, Discounts $discounts): void
    {
        $buy = max(1, (int) ($data['buy'] ?? 1));
        $get = max(1, (int) ($data['get'] ?? 1));
        $factor = BigDecimal::of((string) min(100, max(0, (float) ($data['percent'] ?? 100))))->dividedBy(100, 8, RoundingMode::HalfUp);

        $items = $context->items()->filter(fn (CartItemDTO $item) => $context->inScope($item, $data));
        $units = (int) $items->sum('quantity');
        $free = intdiv($units, $buy + $get) * $get;

        // The cheapest units are the discounted ones.
        foreach ($items->sortBy(fn (CartItemDTO $item) => $item->getUnitPrice()->getMinorAmount()->toInt()) as $item) {
            if ($free <= 0) {
                break;
            }

            $count = min($free, $item->quantity);
            $free -= $count;

            $discounts->off($item, $item->getUnitPrice()->multipliedBy($count)->multipliedBy($factor, RoundingMode::HalfUp));
        }
    }
}
