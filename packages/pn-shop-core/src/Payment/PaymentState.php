<?php

namespace PnShop\Payment;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * State of one payment attempt (an order can have several).
 */
enum PaymentState: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Authorized = 'authorized';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Authorized => 'Authorized',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
            self::PartiallyRefunded => 'Partially refunded',
            self::Refunded => 'Refunded',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Authorized => 'info',
            self::Paid => 'success',
            self::Failed => 'danger',
            self::Cancelled, self::PartiallyRefunded, self::Refunded => 'gray',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Authorized], true);
    }
}
