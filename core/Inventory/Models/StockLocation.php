<?php

namespace PnShop\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A place stock is kept. One default location is created at install.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_default
 * @property bool $is_active
 */
class StockLocation extends Model
{
    /** @var list<string> */
    protected $fillable = ['code', 'name', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    public static function default(): self
    {
        return static::query()->where('is_default', true)->firstOrFail();
    }
}
