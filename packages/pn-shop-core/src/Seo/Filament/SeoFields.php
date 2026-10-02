<?php

namespace PnShop\Seo\Filament;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

/**
 * Meta title and description fields for forms of translatable models with those columns.
 */
final class SeoFields
{
    public static function section(): Section
    {
        return Section::make('Search engines')
            ->description('How this appears in search results. Empty fields use the title and description.')
            ->columns(2)
            ->collapsible()
            ->collapsed()
            ->schema([
                TextInput::make('meta_title')->maxLength(255)->helperText('Best under 60 characters.'),
                Textarea::make('meta_description')->rows(2)->maxLength(500)->helperText('Best under 160 characters.'),
            ]);
    }

    /**
     * Entries for TranslationsSection::make().
     *
     * @return array<string, Closure(string): Field>
     */
    public static function translations(): array
    {
        return [
            'meta_title' => fn (string $name) => TextInput::make($name)->label('Meta title')->maxLength(255),
            'meta_description' => fn (string $name) => Textarea::make($name)->label('Meta description')->rows(2)->maxLength(500),
        ];
    }
}
