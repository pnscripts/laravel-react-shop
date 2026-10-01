<?php

namespace PnShop\Shipping\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PnShop\Shipping\Factories\ShippingZoneFactory;

/**
 * A group of destinations with the same shipping options. Zones are checked in order;
 * the first that matches the address is used.
 *
 * @property int $id
 * @property string $name
 * @property list<string>|null $countries ISO codes; empty means every country
 * @property list<string>|null $postcodes patterns with * wildcards; empty means every postcode
 * @property int $position
 */
class ShippingZone extends Model
{
    /** @use HasFactory<ShippingZoneFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['name', 'countries', 'postcodes', 'position'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['countries' => 'array', 'postcodes' => 'array', 'position' => 'integer'];
    }

    /**
     * @return HasMany<ShippingMethod, $this>
     */
    public function methods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class)->orderBy('position')->orderBy('id');
    }

    public function matches(string $countryCode, ?string $postcode): bool
    {
        if ($this->countries !== null && $this->countries !== [] && ! in_array(strtoupper($countryCode), $this->countries, true)) {
            return false;
        }

        if ($this->postcodes === null || $this->postcodes === []) {
            return true;
        }

        $postcode = strtoupper(str_replace(' ', '', (string) $postcode));

        foreach ($this->postcodes as $pattern) {
            $regex = '/^'.str_replace('\*', '.*', preg_quote(strtoupper(str_replace(' ', '', $pattern)), '/')).'$/';

            if ($postcode !== '' && preg_match($regex, $postcode)) {
                return true;
            }
        }

        return false;
    }

    protected static function newFactory(): ShippingZoneFactory
    {
        return ShippingZoneFactory::new();
    }
}
