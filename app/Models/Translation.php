<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Translation extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['translatable_id', 'translatable_type', 'field', 'locale', 'value'];

    /**
     * Get all of the owning translatable models.
     */
    public function translatable()
    {
        return $this->morphTo();
    }
}
