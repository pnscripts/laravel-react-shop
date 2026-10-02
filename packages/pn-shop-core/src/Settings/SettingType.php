<?php

namespace PnShop\Settings;

enum SettingType: string
{
    case String = 'string';
    case Text = 'text';
    case Email = 'email';
    case Url = 'url';
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Select = 'select';
    /** API keys and passwords: encrypted at rest and never sent back to the browser. */
    case Secret = 'secret';
    /** A hex colour such as #4f46e5. */
    case Color = 'color';

    public function cast(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($this) {
            self::Boolean => filter_var($value, FILTER_VALIDATE_BOOL),
            self::Integer => (int) $value,
            self::Decimal => (float) $value,
            default => (string) $value,
        };
    }

    /**
     * @return list<string>
     */
    public function rules(): array
    {
        return match ($this) {
            self::String, self::Select => ['string', 'max:255'],
            self::Secret => ['string', 'max:2000'],
            self::Color => ['string', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            self::Text => ['string', 'max:65535'],
            self::Email => ['email', 'max:255'],
            self::Url => ['url', 'max:2048'],
            self::Boolean => ['boolean'],
            self::Integer => ['integer'],
            self::Decimal => ['numeric'],
        };
    }
}
