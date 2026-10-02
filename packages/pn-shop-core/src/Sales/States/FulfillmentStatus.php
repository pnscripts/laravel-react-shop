<?php

namespace PnShop\Sales\States;

/**
 * Whether the goods have left the warehouse.
 */
enum FulfillmentStatus: string implements OrderState
{
    case Unfulfilled = 'unfulfilled';
    case PartiallyFulfilled = 'partially_fulfilled';
    case Fulfilled = 'fulfilled';
    case Returned = 'returned';

    public static function field(): string
    {
        return 'fulfillment_status';
    }

    public static function fieldLabel(): string
    {
        return 'Fulfillment';
    }

    public function label(): string
    {
        return match ($this) {
            self::Unfulfilled => 'Not shipped',
            self::PartiallyFulfilled => 'Partially shipped',
            self::Fulfilled => 'Shipped',
            self::Returned => 'Returned',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unfulfilled => 'warning',
            self::PartiallyFulfilled => 'info',
            self::Fulfilled => 'success',
            self::Returned => 'gray',
        };
    }

    public function transitions(): array
    {
        return match ($this) {
            self::Unfulfilled => [self::PartiallyFulfilled, self::Fulfilled],
            self::PartiallyFulfilled => [self::Fulfilled],
            self::Fulfilled => [self::Returned],
            self::Returned => [],
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return $this->color();
    }

    public function canTransitionTo(OrderState $state): bool
    {
        return in_array($state, $this->transitions(), true);
    }
}
