<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One language of a ProductCategory, see PnShop\Localization\Concerns\Translatable.
 */
class ProductCategoryTranslation extends Model
{
    /** @var list<string> */
    protected $guarded = ['id'];
}
