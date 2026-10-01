<?php

namespace PnShop\Localization\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use PnShop\Localization\Localization;

/**
 * Per-entity translation tables.
 *
 * The model's own columns hold the default (source) language. Other languages live in a
 * "<model>_translations" table (model `<Model>Translation` in the same namespace) with one
 * row per locale and one column per translatable attribute.
 *
 * Reading `$product->title` returns the current locale's value, falling back to the
 * source language. Outside the default language a global scope eager-loads only the
 * current locale's rows, so a page costs one extra query per model type, not per field.
 *
 * Using models declare `protected array $translatable = ['title', ...];`.
 *
 * @mixin Model
 */
trait Translatable
{
    /** @var array<string, array<string, string|null>> translations to store on save, by locale */
    private array $pendingTranslations = [];

    public static function bootTranslatable(): void
    {
        static::saved(function (Model $model): void {
            /** @var static $model */
            $model->applyPendingTranslations();
        });

        static::addGlobalScope('translations', function (Builder $query): void {
            $locale = app()->getLocale();

            if ($locale !== app(Localization::class)->defaultLocale()) {
                $query->with(['translations' => fn ($translations) => $translations->where('locale', $locale)]);
            }
        });
    }

    /**
     * @return HasMany<Model, $this>
     */
    public function translations(): HasMany
    {
        /** @var class-string<Model> $model */
        $model = static::class.'Translation';

        return $this->hasMany($model, $this->translationForeignKey());
    }

    /**
     * Column on the translation table that points to this model.
     */
    protected function translationForeignKey(): string
    {
        return $this->getForeignKey();
    }

    /**
     * @return list<string>
     */
    public function translatableAttributes(): array
    {
        return $this->translatable;
    }

    public function getAttribute($key)
    {
        if (in_array($key, $this->translatableAttributes(), true)) {
            $translated = $this->translation($key);

            if ($translated !== null) {
                return $translated;
            }
        }

        return parent::getAttribute($key);
    }

    /**
     * The translation rows are an implementation detail; serialized models show translated attributes instead.
     *
     * @return array<int, string>
     */
    public function getHidden(): array
    {
        return [...parent::getHidden(), 'translations'];
    }

    /**
     * Serialize translatable attributes in the current locale, like attribute reads.
     *
     * @return array<string, mixed>
     */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        foreach ($this->translatableAttributes() as $key) {
            if (array_key_exists($key, $attributes) && ($translated = $this->translation($key)) !== null) {
                $attributes[$key] = $translated;
            }
        }

        return $attributes;
    }

    /**
     * The stored translation of one attribute, or null when there is none (or for the default language).
     */
    public function translation(string $key, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        if ($locale === app(Localization::class)->defaultLocale() || ! $this->exists) {
            return null;
        }

        if ($locale !== app()->getLocale()) {
            // The eager-loaded relation only holds the current locale.
            $value = $this->translations()->where('locale', $locale)->value($key);
        } else {
            if (! $this->relationLoaded('translations')) {
                $this->setRelation('translations', $this->translations()->where('locale', $locale)->get());
            }

            $value = $this->getRelation('translations')->firstWhere('locale', $locale)?->getAttribute($key);
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Write-only attribute for inline editors: `['bg' => ['title' => '…']]`, stored when the model is saved.
     */
    public function setTranslationsInputAttribute(mixed $value): void
    {
        $this->pendingTranslations = is_array($value) ? $value : [];
    }

    /**
     * Current translations of every non-default active language, in the `translations_input` shape.
     *
     * @return array<string, array<string, string|null>>
     */
    public function translationsInput(): array
    {
        $input = [];

        foreach (app(Localization::class)->languages() as $language) {
            if ($language->is_default) {
                continue;
            }

            foreach ($this->translatableAttributes() as $attribute) {
                $input[$language->code][$attribute] = $this->translation($attribute, $language->code);
            }
        }

        return $input;
    }

    private function applyPendingTranslations(): void
    {
        $pending = $this->pendingTranslations;
        $this->pendingTranslations = [];

        foreach ($pending as $locale => $values) {
            $values = array_map(fn (mixed $value) => is_string($value) && trim($value) !== '' ? $value : null, (array) $values);

            if (array_filter($values) !== [] || $this->hasTranslation((string) $locale)) {
                $this->setTranslations((string) $locale, $values);
            }
        }
    }

    public function hasTranslation(string $locale): bool
    {
        return $this->translations()->where('locale', $locale)->exists();
    }

    /**
     * Store one language's values. For the default language the model's own columns are updated.
     *
     * @param  array<string, string|null>  $values
     */
    public function setTranslations(string $locale, array $values): void
    {
        $values = array_intersect_key($values, array_flip($this->translatableAttributes()));

        if ($locale === app(Localization::class)->defaultLocale()) {
            $this->fill($values)->save();

            return;
        }

        if (in_array('slug', $this->translatableAttributes(), true)) {
            $values['slug'] = $this->uniqueTranslatedSlug($locale, $values['slug'] ?? null, $values['title'] ?? null);
        }

        $this->translations()->updateOrCreate(['locale' => $locale], $values);
        $this->unsetRelation('translations');
    }

    /**
     * Match an attribute in the current locale's translation or in the source language.
     *
     * @param  Builder<Model>  $query
     */
    public function scopeWhereTranslated(Builder $query, string $attribute, mixed $value): void
    {
        $locale = app()->getLocale();

        $query->where(fn (Builder $query) => $query
            ->where($attribute, $value)
            ->when(
                $locale !== app(Localization::class)->defaultLocale(),
                fn (Builder $query) => $query->orWhereHas('translations', fn (Builder $translation) => $translation->where('locale', $locale)->where($attribute, $value)),
            ));
    }

    /**
     * Find a model by a (possibly translated) attribute in the current locale.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $field ??= $this->getRouteKeyName();
        $locale = app()->getLocale();

        if (! in_array($field, $this->translatableAttributes(), true) || $locale === app(Localization::class)->defaultLocale()) {
            return parent::resolveRouteBinding($value, $field);
        }

        return static::query()->whereTranslated($field, $value)->first();
    }

    private function uniqueTranslatedSlug(string $locale, ?string $slug, ?string $title): ?string
    {
        $base = Str::slug($slug ?: (string) $title);

        if ($base === '') {
            return null;
        }

        $candidate = $base;

        for ($i = 2; $this->slugTaken($locale, $candidate); $i++) {
            $candidate = "{$base}-{$i}";
        }

        return $candidate;
    }

    private function slugTaken(string $locale, string $slug): bool
    {
        $foreignKey = $this->translationForeignKey();

        return $this->translations()->getRelated()->newQuery()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where($foreignKey, '!=', $this->getKey())
            ->exists()
            || static::query()->withoutGlobalScopes()->where('slug', $slug)->whereKeyNot($this->getKey())->exists();
    }
}
