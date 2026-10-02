<?php

namespace PnShop\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PnShop\Cms\Menus;

/**
 * @property int $id
 * @property string $code e.g. header, footer
 * @property string $name
 */
class Menu extends Model
{
    /** @var list<string> */
    protected $fillable = ['code', 'name'];

    protected static function booted(): void
    {
        static::saved(fn () => Menus::flush());
        static::deleted(fn () => Menus::flush());
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('position')->orderBy('id');
    }
}
