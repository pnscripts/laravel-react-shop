<?php

namespace PnShop\Returns;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Where a return request stands. Staff move it along the allowed transitions.
 */
enum ReturnStatus: string implements HasColor, HasLabel
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Received = 'received';
    case Refunded = 'refunded';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Received => 'Received',
            self::Refunded => 'Refunded',
            self::Closed => 'Closed',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Requested => 'warning',
            self::Approved, self::Received => 'info',
            self::Refunded => 'success',
            self::Rejected => 'danger',
            self::Closed => 'gray',
        };
    }

    /**
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Requested => [self::Approved, self::Rejected],
            // Closed without receiving: the customer never sent the items.
            self::Approved => [self::Received, self::Closed],
            // Closed without a refund: exchanged or repaired instead.
            self::Received => [self::Refunded, self::Closed],
            self::Refunded => [self::Closed],
            self::Rejected, self::Closed => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->transitions(), true);
    }

    /** Still holds the units it asks to return (they cannot be requested again). */
    public function isOpen(): bool
    {
        return in_array($this, [self::Requested, self::Approved, self::Received], true);
    }
}
