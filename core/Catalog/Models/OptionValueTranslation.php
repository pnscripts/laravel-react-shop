<?php

namespace PnShop\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One language of an OptionValue, see PnShop\Localization\Concerns\Translatable.
 */
class OptionValueTranslation extends Model
{
    /** @var list<string> */
    protected $guarded = ['id'];
}
