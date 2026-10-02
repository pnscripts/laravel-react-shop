<?php

namespace PnShop\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One language of a Category, see PnShop\Localization\Concerns\Translatable.
 */
class CategoryTranslation extends Model
{
    /** @var list<string> */
    protected $guarded = ['id'];

    protected $table = 'product_category_translations';
}
