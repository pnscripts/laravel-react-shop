<?php

namespace PnShop\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PnShop\Cms\MenuItemType;
use PnShop\Cms\Menus;
use PnShop\Localization\Concerns\Translatable;
use PnShop\Localization\Contracts\TranslatableModel;

/**
 * @property int $id
 * @property int $menu_id
 * @property int|null $parent_id
 * @property int $position
 * @property MenuItemType $type
 * @property int|null $target_id
 * @property string|null $url
 * @property string|null $label empty uses the target's own name
 * @property bool $new_tab
 */
class MenuItem extends Model implements TranslatableModel
{
    use Translatable;

    /** @var list<string> */
    protected $fillable = ['menu_id', 'parent_id', 'position', 'type', 'target_id', 'url', 'label', 'new_tab'];

    /** @var list<string> */
    protected array $translatable = ['label'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['type' => MenuItemType::class, 'new_tab' => 'boolean', 'position' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Menus::flush());
        static::deleted(fn () => Menus::flush());
    }

    /**
     * @return BelongsTo<Menu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * @return BelongsTo<MenuItem, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('id');
    }
}
