<?php

namespace PnShop\Localization\Models;

use Illuminate\Database\Eloquent\Model;
use PnShop\Localization\Localization;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $native_name
 * @property bool $is_default
 * @property bool $is_active
 * @property int $sort_order
 */
class Language extends Model
{
    /** @var list<string> */
    protected $fillable = ['code', 'name', 'native_name', 'is_active', 'sort_order'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(Localization::class)->flush());
        static::deleted(fn () => app(Localization::class)->flush());
    }
}
