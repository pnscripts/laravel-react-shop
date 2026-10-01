<?php

namespace PnShop\Media\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One language of a Media item's alt text and title.
 */
class MediaTranslation extends Model
{
    /** @var list<string> */
    protected $guarded = ['id'];
}
