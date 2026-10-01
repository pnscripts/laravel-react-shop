<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One language of a Product, see PnShop\Localization\Concerns\Translatable.
 */
class ProductTranslation extends Model
{
    /** @var list<string> */
    protected $guarded = ['id'];
}
