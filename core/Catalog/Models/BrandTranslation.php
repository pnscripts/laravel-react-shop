<?php

namespace PnShop\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One language of a Brand, see PnShop\Localization\Concerns\Translatable.
 */
class BrandTranslation extends Model
{
    /** @var list<string> */
    protected $guarded = ['id'];
}
