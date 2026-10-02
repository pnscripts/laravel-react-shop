<?php

namespace PnShop\Promotion\Contracts;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component as SchemaComponent;
use PnShop\Promotion\PromotionContext;

/**
 * A kind of promotion condition ("cart subtotal at least", "customer group is", ...).
 * Registered in PromotionRegistry; core and plugins add their own.
 */
interface ConditionType
{
    /** Stable key stored with the promotion, e.g. "subtotal". */
    public function key(): string;

    public function label(): string;

    /**
     * Admin form fields for the condition's settings (state paths relative to its data).
     *
     * @return list<Field|SchemaComponent>
     */
    public function fields(): array;

    /**
     * Validation rules for the settings, keyed like the fields.
     *
     * @return array<string, mixed>
     */
    public function rules(): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function passes(PromotionContext $context, array $data): bool;
}
