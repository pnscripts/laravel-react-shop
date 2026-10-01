<?php

namespace PnShop\Sales\States;

/**
 * Where the order is in its life: pending → processing → completed, or cancelled.
 */
enum OrderStatus: string implements OrderState
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public static function field(): string
    {
        return 'status';
    }

    public static function fieldLabel(): string
    {
        return 'Status';
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Processing => 'info',
            self::Completed => 'success',
            self::Cancelled => 'gray',
        };
    }

    public function transitions(): array
    {
        return match ($this) {
            self::Pending => [self::Processing, self::Cancelled],
            self::Processing => [self::Completed, self::Cancelled],
            self::Completed => [self::Processing],
            self::Cancelled => [self::Pending],
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
