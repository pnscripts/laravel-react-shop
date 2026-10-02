<?php

namespace PnShop\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One language of an Option, see PnShop\Localization\Concerns\Translatable.
 */
class OptionTranslation extends Model
{
    /** @var list<string> */
    protected $guarded = ['id'];
}
