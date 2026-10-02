<?php

namespace PnShop\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PnShop\Localization\Concerns\Translatable;
use PnShop\Localization\Contracts\TranslatableModel;

/**
 * One value of an option, e.g. "M" for Size.
 *
 * @property int $id
 * @property int $option_id
 * @property string $value
 * @property int $position
 */
class OptionValue extends Model implements TranslatableModel
{
    use Translatable;

    /** @var list<string> */
    protected $fillable = ['option_id', 'value', 'position', 'translations_input'];

    /** @var list<string> */
    protected array $translatable = ['value'];

    /**
     * @return BelongsTo<Option, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }
}
