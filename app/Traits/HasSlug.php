<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function (self $model) {
            // If the slug is NOT set, generate it from title using attributes
            if (empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($model);
            } else {
                // Even if set manually, still make sure it's unique
                $model->slug = static::makeSlugUnique($model, $model->slug);
            }
        });

        static::updating(function (self $model) {
            // Only regenerate if relevant fields changed (title or slug)
            if ($model->isDirty('title') || $model->isDirty('slug')) {
                $model->slug = static::generateUniqueSlug($model);
            }
        });
    }

    /**
     * Generate a unique slug from title or fallback to 'item'.
     */
    protected static function generateUniqueSlug(Model $model): string
    {
        // Check if the slug is set in the attributes, if not, use title or fallback
        $slug = isset($model->attributes['slug']) ? $model->attributes['slug'] : null;
        $title = isset($model->attributes['title']) ? $model->attributes['title'] : 'item'; // Default to 'item'

        // Use slug or title for the base, fallback to 'item' if both are missing
        $base = Str::slug($slug ?: $title);

        return static::makeSlugUnique($model, $base);
    }

    /**
     * Ensure slug uniqueness (e.g., slug, slug-1, slug-2...).
     */
    protected static function makeSlugUnique(Model $model, string $base): string
    {
        $slug = $base;
        $i = 1;

        // Check for existing slugs and append a counter if necessary
        while (
            $model->newQueryWithoutScopes()
                ->where('slug', $slug)
                ->when($model->exists, fn ($q) => $q->whereKeyNot($model->getKey()))
                ->exists()
        ) {
            $slug = $base.'-'.$i++; // Add counter to make slug unique
        }

        return $slug;
    }
}
