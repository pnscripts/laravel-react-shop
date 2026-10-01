<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One language of a ProductAttribute, see PnShop\Localization\Concerns\Translatable.
 */
class ProductAttributeTranslation extends Model
{
    /** @var list<string> */
    protected $guarded = ['id'];
}
