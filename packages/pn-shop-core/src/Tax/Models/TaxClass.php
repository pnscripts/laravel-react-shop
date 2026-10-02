<?php

namespace PnShop\Tax\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * What kind of goods a rate applies to: standard, reduced, zero-rated, ...
 *
 * @property int $id
 * @property string $name
 * @property bool $is_default used by products without a class
 */
class TaxClass extends Model
{
    /** @var list<string> */
    protected $fillable = ['name', 'is_default'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    protected static function booted(): void
    {
        // Only one class is the default.
        static::saved(function (TaxClass $class): void {
            if ($class->is_default) {
                static::query()->whereKeyNot($class->id)->where('is_default', true)->update(['is_default' => false]);
            }
        });
    }

    public static function defaultId(): ?int
    {
        $id = static::query()->where('is_default', true)->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @return HasMany<TaxRate, $this>
     */
    public function rates(): HasMany
    {
        return $this->hasMany(TaxRate::class);
    }
}
