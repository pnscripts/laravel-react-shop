<?php

namespace PnShop\Localization\Filament;

use LogicException;
use PnShop\Localization\Contracts\TranslatableModel;
use PnShop\Localization\Localization;
use PnShop\Localization\Models\Language;

/**
 * For Create/Edit pages of translatable models that use {@see TranslationsSection}.
 */
trait SavesTranslations
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->translatableRecord();

        foreach ($this->translationLocales() as $locale) {
            foreach ($record->translatableAttributes() as $attribute) {
                $data['translations'][$locale][$attribute] = $record->translation($attribute, $locale);
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->saveTranslations();
    }

    protected function afterSave(): void
    {
        $this->saveTranslations();
    }

    private function saveTranslations(): void
    {
        $record = $this->translatableRecord();
        $translations = (array) ($this->data['translations'] ?? []);

        foreach ($this->translationLocales() as $locale) {
            $values = array_map(
                fn (mixed $value) => is_string($value) && trim($value) !== '' ? $value : null,
                (array) ($translations[$locale] ?? []),
            );

            if (array_filter($values) === [] && ! $record->hasTranslation($locale)) {
                continue;
            }

            $record->setTranslations($locale, $values);
        }
    }

    private function translatableRecord(): TranslatableModel
    {
        $record = $this->getRecord();

        if (! $record instanceof TranslatableModel) {
            throw new LogicException($record::class.' must implement '.TranslatableModel::class.' to use '.self::class.'.');
        }

        return $record;
    }

    /**
     * @return list<string>
     */
    private function translationLocales(): array
    {
        $languages = app(Localization::class)->languages()->reject(fn (Language $language) => $language->is_default);

        return array_values(array_map(fn (Language $language) => $language->code, $languages->all()));
    }
}
