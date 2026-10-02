<?php

namespace PnShop\Tax\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A percentage charged for a class in a zone. Rates with the same priority add up;
 * compound rates are charged on the amount including the earlier rates.
 *
 * @property int $id
 * @property int $tax_zone_id
 * @property int $tax_class_id
 * @property string $name e.g. "VAT 20%"
 * @property string $rate percent, e.g. "20.0000"
 * @property int $priority
 * @property bool $is_compound
 */
class TaxRate extends Model
{
    /** @var list<string> */
    protected $fillable = ['tax_zone_id', 'tax_class_id', 'name', 'rate', 'priority', 'is_compound'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['rate' => 'decimal:4', 'priority' => 'integer', 'is_compound' => 'boolean'];
    }

    /**
     * @return BelongsTo<TaxZone, $this>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(TaxZone::class, 'tax_zone_id');
    }

    /**
     * @return BelongsTo<TaxClass, $this>
     */
    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }
}
