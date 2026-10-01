<?php

namespace PnShop\Localization\Models;

use Illuminate\Database\Eloquent\Model;
use Locale;

/**
 * @property int $id
 * @property string $code ISO 3166-1 alpha-2
 * @property bool $is_active
 */
class Country extends Model
{
    /** @var list<string> */
    protected $fillable = ['code', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * The country name in the given (or current) language, from ICU data.
     */
    public function name(?string $locale = null): string
    {
        return Locale::getDisplayRegion('-'.$this->code, $locale ?? app()->getLocale()) ?: $this->code;
    }
}
