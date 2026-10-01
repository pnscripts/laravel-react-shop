<?php

namespace PnShop\Foundation\Concerns;

trait HasSortOrder
{
    /**
     * Boot the trait to handle sort_order automatically.
     */
    public static function bootHasSortOrder(): void
    {
        static::creating(function ($model) {
            // If no sort_order is provided, set it based on the highest existing value
            if (is_null($model->sort_order)) {
                $model->sort_order = static::getNextSortOrder();
            }
        });

        static::updating(function ($model) {
            // Ensure sort_order is set even on update
            if (is_null($model->sort_order)) {
                $model->sort_order = static::getNextSortOrder();
            }
        });
    }

    /**
     * Get the next available sort_order.
     * This is based on the highest sort_order in the table.
     */
    protected static function getNextSortOrder(): int
    {
        return static::max('sort_order') + 1;  // Increment the highest value by 1
    }
}
