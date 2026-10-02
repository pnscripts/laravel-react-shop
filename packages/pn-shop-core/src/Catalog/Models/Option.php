<?php

namespace PnShop\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PnShop\Localization\Concerns\Translatable;
use PnShop\Localization\Contracts\TranslatableModel;

/**
 * A variant axis such as Size or Color.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $position
 */
class Option extends Model implements TranslatableModel
{
    use Translatable;

    /** @var list<string> */
    protected $fillable = ['code', 'name', 'position', 'translations_input'];

    /** @var list<string> */
    protected array $translatable = ['name'];

    /**
     * @return HasMany<OptionValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(OptionValue::class)->orderBy('position')->orderBy('id');
    }
}
