<?php

namespace PnShop\Settings\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $namespace
 * @property string $key
 * @property mixed $value
 */
class Setting extends Model
{
    /** @var list<string> */
    protected $fillable = ['namespace', 'key', 'value'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['value' => 'json'];
    }
}
