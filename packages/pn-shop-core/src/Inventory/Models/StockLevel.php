<?php

namespace PnShop\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Quantity of one variant at one location. `available` = on_hand - reserved.
 *
 * @property int $id
 * @property int $product_variant_id
 * @property int $stock_location_id
 * @property int $on_hand
 * @property int $reserved
 */
class StockLevel extends Model
{
    /** @var list<string> */
    protected $fillable = ['product_variant_id', 'stock_location_id', 'on_hand', 'reserved'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['on_hand' => 'integer', 'reserved' => 'integer'];
    }

    /**
     * @return BelongsTo<StockLocation, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    public function available(): int
    {
        return $this->on_hand - $this->reserved;
    }
}
