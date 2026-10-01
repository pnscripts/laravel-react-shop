<?php

namespace App\Traits;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasTranslations
{
    /**
     * Get the translated value for a specific field.
     */
    public function translate(string $field, string $locale): ?string
    {
        return $this->translations()
            ->where('field', $field)
            ->where('locale', $locale)
            ->value('value');
    }

    /**
     * Set the translation for a specific field.
     */
    public function setTranslation(string $field, string $locale, string $value): void
    {
        $this->translations()->updateOrCreate(
            ['field' => $field, 'locale' => $locale],
            ['value' => $value]
        );
    }

    /**
     * Get the translated attribute or fallback to the original field value.
     */
    public function getTranslatedAttribute(string $field, ?string $locale = null): string
    {
        // Use the application's default locale if no specific locale is provided
        $locale = $locale ?? app()->getLocale();

        // Check if the field is translatable
        if (in_array($field, $this->getTranslatableFields())) {
            // Attempt to fetch the translated value
            $translatedValue = $this->translate($field, $locale);

            // Fallback to the original field value if no translation exists
            return $translatedValue ?? (string) $this->getOriginal($field);
        }

        // If not translatable, return the original value of the field
        return (string) $this->getOriginal($field);
    }

    /**
     * Override the `getAttribute()` method to automatically return translated fields if available.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        // Dynamically fetch the translatable fields defined in the model
        $translatableFields = $this->getTranslatableFields();

        // Check if the field is translatable and return the translated value if so
        if (in_array($key, $translatableFields)) {
            return $this->getTranslatedAttribute($key);
        }

        // Fallback to the default attribute value if not translatable
        return parent::getAttribute($key);
    }

    /**
     * Get the translatable fields from the model.
     */
    public function getTranslatableFields(): array
    {
        return $this->translatable;
    }

    /**
     * Relationship to the translations.
     */
    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }
}
