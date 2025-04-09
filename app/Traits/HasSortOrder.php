<?php

namespace App\Traits;

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
     *
     * @return int
     */
    protected static function getNextSortOrder(): int
    {
        return static::max('sort_order') + 1;  // Increment the highest value by 1
    }

    /**
     * Scope: Order categories by sort order, with optional direction and secondary order.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction 'asc' or 'desc'
     * @param string|null $secondary 'created_at' or any other column
     * @param string $secondaryDirection 'asc' or 'desc'
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSortByOrder($query, string $direction = 'asc', string $secondary = null, string $secondaryDirection = 'asc')
    {
        $query->orderBy('sort_order', $direction);

        if ($secondary) {
            $query->orderBy($secondary, $secondaryDirection);
        }

        return $query;
    }
}
