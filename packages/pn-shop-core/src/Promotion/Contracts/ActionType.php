<?php

namespace PnShop\Promotion\Contracts;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component as SchemaComponent;
use PnShop\Promotion\Discounts;
use PnShop\Promotion\PromotionContext;

/**
 * A kind of promotion action ("percent off", "free shipping", ...): it puts discounts on
 * cart lines through Discounts, which never lets a line go below zero.
 */
interface ActionType
{
    public function key(): string;

    public function label(): string;

    /**
     * @return list<Field|SchemaComponent>
     */
    public function fields(): array;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function apply(PromotionContext $context, array $data, Discounts $discounts): void;
}
