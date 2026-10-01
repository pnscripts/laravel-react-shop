<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One language of a ProductAttributeValue, see PnShop\Localization\Concerns\Translatable.
 */
class ProductAttributeValueTranslation extends Model
{
    /** @var list<string> */
    protected $guarded = ['id'];
}
