<?php

namespace PnShop\Shipping\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use PnShop\Localization\Concerns\Translatable;
use PnShop\Localization\Contracts\TranslatableModel;
use PnShop\Shipping\Contracts\ShippingCarrier;
use PnShop\Shipping\Factories\ShippingMethodFactory;
use PnShop\Shipping\ShippingCarrierManager;
use PnShop\Tax\Models\TaxClass;

/**
 * A delivery option in a zone: a carrier with the merchant's settings.
 *
 * @property int $id
 * @property int $shipping_zone_id
 * @property string $name
 * @property string|null $description
 * @property string $carrier
 * @property int|null $tax_class_id null uses the default tax class
 * @property array<string, mixed>|null $settings
 * @property bool $is_active
 * @property int $position
 * @property-read ShippingZone $zone
 */
class ShippingMethod extends Model implements TranslatableModel
{
    /** @use HasFactory<ShippingMethodFactory> */
    use HasFactory, SoftDeletes, Translatable;

    /** @var list<string> */
    protected $fillable = ['shipping_zone_id', 'name', 'description', 'carrier', 'tax_class_id', 'settings', 'is_active', 'position'];

    /** @var list<string> */
    protected array $translatable = ['name', 'description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['settings' => 'array', 'is_active' => 'boolean', 'position' => 'integer'];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @return BelongsTo<ShippingZone, $this>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    /**
     * @return BelongsTo<TaxClass, $this>
     */
    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }

    public function carrierInstance(): ?ShippingCarrier
    {
        $carriers = app(ShippingCarrierManager::class);

        return $carriers->has($this->carrier) ? $carriers->get($this->carrier) : null;
    }

    /**
     * A carrier setting, falling back to the carrier's default when it is not filled in.
     */
    public function setting(string $key): mixed
    {
        $value = $this->settings[$key] ?? null;

        if ($value !== null && $value !== '') {
            return $value;
        }

        foreach ($this->carrierInstance()?->settings() ?? [] as $definition) {
            if ($definition->key === $key) {
                return $definition->default;
            }
        }

        return null;
    }

    protected static function newFactory(): ShippingMethodFactory
    {
        return ShippingMethodFactory::new();
    }
}
