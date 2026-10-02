<?php

namespace PnShop\Promotion\Conditions;

use Filament\Forms\Components\Select;
use PnShop\Customer\Models\CustomerGroup;
use PnShop\Promotion\Contracts\ConditionType;
use PnShop\Promotion\PromotionContext;

/**
 * The signed-in customer belongs to one of the groups. Guests never match.
 */
class CustomerGroupCondition implements ConditionType
{
    public function key(): string
    {
        return 'customer_group';
    }

    public function label(): string
    {
        return 'Customer group is';
    }

    public function fields(): array
    {
        return [Select::make('group_ids')->label('Customer groups')->multiple()->options(fn () => CustomerGroup::query()->pluck('name', 'id')->all())->required()];
    }

    public function rules(): array
    {
        return ['group_ids' => ['required', 'array', 'min:1'], 'group_ids.*' => ['integer']];
    }

    public function passes(PromotionContext $context, array $data): bool
    {
        $group = $context->customer()?->customer_group_id;

        return $group !== null && in_array($group, array_map('intval', (array) ($data['group_ids'] ?? [])), true);
    }
}
