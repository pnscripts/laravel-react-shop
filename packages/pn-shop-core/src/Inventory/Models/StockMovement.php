<?php

namespace PnShop\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use PnShop\Acl\Models\AdminUser;
use PnShop\Catalog\Models\ProductVariant;
use PnShop\Inventory\StockMovementReason;

/**
 * One change to stock, kept as an append-only ledger.
 *
 * @property int $id
 * @property int $product_variant_id
 * @property int $stock_location_id
 * @property int $quantity
 * @property int $on_hand_after
 * @property StockMovementReason $reason
 * @property string|null $note
 * @property Carbon $created_at
 */
class StockMovement extends Model
{
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['reason' => StockMovementReason::class, 'quantity' => 'integer', 'on_hand_after' => 'integer'];
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return BelongsTo<StockLocation, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
