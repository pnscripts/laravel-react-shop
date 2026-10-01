<?php

namespace PnShop\Localization\Models;

use Brick\Money\Currency as BrickCurrency;
use Illuminate\Database\Eloquent\Model;
use PnShop\Localization\Localization;

/**
 * @property int $id
 * @property string $code ISO 4217
 * @property string $name
 * @property string $exchange_rate units of this currency per one unit of the default currency
 * @property bool $is_default
 * @property bool $is_active
 */
class Currency extends Model
{
    /** @var list<string> */
    protected $fillable = ['code', 'name', 'exchange_rate', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['exchange_rate' => 'decimal:8', 'is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    /**
     * Digits after the decimal point (2 for EUR, 0 for JPY).
     */
    public function decimals(): int
    {
        return BrickCurrency::of($this->code)->getDefaultFractionDigits();
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(Localization::class)->flush());
        static::deleted(fn () => app(Localization::class)->flush());
    }
}
