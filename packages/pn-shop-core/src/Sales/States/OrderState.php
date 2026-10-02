<?php

namespace PnShop\Sales\States;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * One of an order's three independent states. Each is a small state machine: the
 * allowed next states are declared in code and enforced by OrderWorkflow.
 *
 * Implemented by string-backed enums only.
 *
 * @property-read string $value
 */
interface OrderState extends BackedEnum, HasColor, HasLabel
{
    /** The orders column holding this state. */
    public static function field(): string;

    /** Human name of the state machine ("Status", "Payment", ...). */
    public static function fieldLabel(): string;

    public function label(): string;

    /** Filament badge color. */
    public function color(): string;

    /**
     * @return list<static>
     */
    public function transitions(): array;

    public function canTransitionTo(self $state): bool;
}
