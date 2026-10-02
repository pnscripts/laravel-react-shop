<?php

namespace PnShop\Localization\Filament;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use PnShop\Localization\Localization;
use PnShop\Localization\Models\Language;

/**
 * A form section with one tab per non-default language for a translatable model.
 * The fields write to `translations.<locale>.<attribute>` and are saved by the
 * page trait {@see SavesTranslations}. Empty fields fall back to the default language.
 */
final class TranslationsSection
{
    /**
     * @param  array<string, Closure(string $attribute): Field>  $fields  attribute => field factory
     */
    public static function make(array $fields): Section
    {
        $languages = app(Localization::class)->languages()->reject(fn (Language $language) => $language->is_default);

        return Section::make('Translations')
            ->description('Leave a field empty to show the default language text.')
            ->collapsible()
            ->visible($languages->isNotEmpty())
            ->schema([
                Tabs::make('translations')->tabs($languages->map(fn (Language $language) => Tab::make($language->native_name)
                    ->schema(array_map(
                        fn (string $attribute, Closure $factory) => $factory("translations.{$language->code}.{$attribute}")
                            ->dehydrated(false),
                        array_keys($fields),
                        $fields,
                    )))->values()->all()),
            ]);
    }
}
