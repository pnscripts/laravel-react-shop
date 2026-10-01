<?php

namespace PnShop\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PnShop\Acl\Models\AdminUser;
use PnShop\Catalog\Models\ProductVariant;
use PnShop\Inventory\Exceptions\InsufficientStock;
use PnShop\Inventory\Models\StockLevel;
use PnShop\Inventory\Models\StockLocation;
use PnShop\Inventory\Models\StockMovement;

/**
 * Every stock change goes through here: levels are updated with a conditional statement
 * (never below zero unless the variant allows backorders) and recorded in the ledger.
 */
final class InventoryService
{
    /**
     * Units that can be sold now, or null when the variant does not track inventory.
     * Uses the variant's loaded `stockLevels` when available.
     */
    public function available(ProductVariant $variant): ?int
    {
        if (! $variant->track_inventory) {
            return null;
        }

        $levels = $variant->relationLoaded('stockLevels') ? $variant->getRelation('stockLevels') : $variant->stockLevels()->get();

        return max(0, (int) $levels->sum(fn (StockLevel $level) => $level->available()));
    }

    public function canSell(ProductVariant $variant, int $quantity): bool
    {
        $available = $this->available($variant);

        return $available === null || $variant->allow_backorder || $available >= $quantity;
    }

    /**
     * Add (positive) or remove (negative) stock at a location and record the movement.
     *
     * @throws InsufficientStock when removing more than is available and backorders are not allowed.
     */
    public function adjust(
        ProductVariant $variant,
        int $quantity,
        StockMovementReason $reason,
        ?Model $reference = null,
        ?AdminUser $admin = null,
        ?string $note = null,
        ?StockLocation $location = null,
        bool $enforceAvailability = true,
    ): StockMovement {
        $location ??= StockLocation::default();

        return DB::transaction(function () use ($variant, $quantity, $reason, $reference, $admin, $note, $location, $enforceAvailability) {
            $level = StockLevel::query()->firstOrCreate(
                ['product_variant_id' => $variant->id, 'stock_location_id' => $location->id],
                ['on_hand' => 0, 'reserved' => 0],
            );

            $update = StockLevel::query()->whereKey($level->id);

            if ($enforceAvailability && $quantity < 0 && $variant->track_inventory && ! $variant->allow_backorder) {
                $update->whereRaw('on_hand - reserved >= ?', [-$quantity]);
            }

            if ($update->increment('on_hand', $quantity) === 0) {
                throw new InsufficientStock("Not enough stock for variant {$variant->id}.");
            }

            $variant->unsetRelation('stockLevels');

            return StockMovement::query()->create([
                'product_variant_id' => $variant->id,
                'stock_location_id' => $location->id,
                'quantity' => $quantity,
                'on_hand_after' => (int) StockLevel::query()->whereKey($level->id)->value('on_hand'),
                'reason' => $reason,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'admin_user_id' => $admin?->id,
                'note' => $note,
            ]);
        });
    }

    /**
     * Set the counted quantity at a location (a stock take), recording the difference.
     */
    public function setOnHand(ProductVariant $variant, int $onHand, ?AdminUser $admin = null, ?string $note = null, ?StockLocation $location = null): ?StockMovement
    {
        $location ??= StockLocation::default();

        $current = (int) StockLevel::query()
            ->where(['product_variant_id' => $variant->id, 'stock_location_id' => $location->id])
            ->value('on_hand');

        if ($onHand === $current) {
            return null;
        }

        // A count is a fact, not a sale: it is recorded even when reservations exceed it.
        return $this->adjust($variant, $onHand - $current, StockMovementReason::Adjustment, admin: $admin, note: $note, location: $location, enforceAvailability: false);
    }
}
