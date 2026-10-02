<?php

namespace PnShop\Returns;

/**
 * Why the customer returns the items.
 */
enum ReturnReason: string
{
    case Damaged = 'damaged';
    case WrongItem = 'wrong_item';
    case NotAsDescribed = 'not_as_described';
    case NoLongerNeeded = 'no_longer_needed';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Damaged => 'Arrived damaged or faulty',
            self::WrongItem => 'Wrong item sent',
            self::NotAsDescribed => 'Not as described',
            self::NoLongerNeeded => 'No longer needed',
            self::Other => 'Other',
        };
    }

    /**
     * @return array<string, string> value => translated label
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = __($case->label());
        }

        return $options;
    }
}
