<?php

namespace PnShop\Seo;

use Illuminate\Database\Eloquent\Model;
use PnShop\Localization\Localization;

/**
 * Adds a 301 redirect whenever a model's slug changes, in the default language (the
 * model's own column) or a translation (the translation row).
 */
final class SlugRedirects
{
    /**
     * @param  class-string<Model>  $model
     * @param  class-string<Model>  $translation
     * @param  \Closure(string): string  $path  path for a slug, without language prefix
     */
    public static function watch(string $model, string $translation, \Closure $path): void
    {
        $model::updated(function (Model $record) use ($path): void {
            if ($record->wasChanged('slug') && filled($record->getOriginal('slug'))) {
                $localization = app(Localization::class);
                $prefix = $localization->prefix($localization->defaultLocale());

                Redirects::add($prefix.$path((string) $record->getOriginal('slug')), $prefix.$path((string) $record->getAttribute('slug')), automatic: true);
            }
        });

        $translation::updated(function (Model $row) use ($path): void {
            if ($row->wasChanged('slug') && filled($row->getOriginal('slug')) && filled($row->getAttribute('slug'))) {
                $prefix = app(Localization::class)->prefix((string) $row->getAttribute('locale'));

                Redirects::add($prefix.$path((string) $row->getOriginal('slug')), $prefix.$path((string) $row->getAttribute('slug')), automatic: true);
            }
        });
    }
}
