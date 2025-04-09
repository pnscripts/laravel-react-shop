<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            // If the slug is NOT set, generate it from title
            if (empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($model);
            } else {
                // Even if set manually, still make sure it's unique
                $model->slug = static::makeSlugUnique($model, $model->slug);
            }
        });
    }

    /**
     * Generate a slug from title (or fallback).
     */
    protected static function generateUniqueSlug($model): string
    {
        $base = Str::slug($model->title ?? 'item'); // fallback if title is missing
        return static::makeSlugUnique($model, $base);
    }

    /**
     * Ensure slug uniqueness (e.g., slug, slug-1, slug-2...).
     */
    protected static function makeSlugUnique($model, string $base): string
    {
        $slug = $base;
        $i = 1;

        while (
            $model->newQueryWithoutScopes()
                ->where('slug', $slug)
                ->when($model->exists, fn ($q) => $q->where('id', '!=', $model->id))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
