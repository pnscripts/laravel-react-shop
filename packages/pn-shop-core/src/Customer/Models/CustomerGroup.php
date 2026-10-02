<?php

namespace PnShop\Customer\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Segments customers (e.g. Retail, Wholesale) for group prices and tax rules.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $is_default
 */
class CustomerGroup extends Model
{
    /** @var list<string> */
    protected $fillable = ['code', 'name'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public static function default(): self
    {
        return static::query()->where('is_default', true)->firstOrFail();
    }

    /**
     * @return HasMany<User, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
