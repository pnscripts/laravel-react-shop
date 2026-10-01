<?php

namespace PnShop\Localization\Contracts;

use Illuminate\Database\Eloquent\Model;
use PnShop\Localization\Concerns\Translatable;

/**
 * A model with per-language values, implemented by {@see Translatable}.
 */
interface TranslatableModel
{
    /**
     * @return list<string>
     */
    public function translatableAttributes(): array;

    public function translation(string $key, ?string $locale = null): ?string;

    /**
     * @param  array<string, string|null>  $values
     */
    public function setTranslations(string $locale, array $values): void;

    public function hasTranslation(string $locale): bool;
}
