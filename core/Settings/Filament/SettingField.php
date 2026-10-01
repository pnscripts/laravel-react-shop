<?php

namespace PnShop\Settings\Filament;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use PnShop\Settings\SettingDefinition;
use PnShop\Settings\SettingType;

/**
 * The admin form field for a typed setting definition. Used by the settings page and
 * by anything else configured with SettingDefinitions (payment gateways, shipping carriers).
 */
final class SettingField
{
    public static function make(SettingDefinition $definition): Field
    {
        $field = match ($definition->type) {
            SettingType::Text => Textarea::make($definition->key)->rows(3),
            SettingType::Boolean => Toggle::make($definition->key),
            SettingType::Select => Select::make($definition->key)->options($definition->options),
            SettingType::Email => TextInput::make($definition->key)->email(),
            SettingType::Url => TextInput::make($definition->key)->url(),
            SettingType::Integer => TextInput::make($definition->key)->integer(),
            SettingType::Decimal => TextInput::make($definition->key)->numeric(),
            SettingType::String => TextInput::make($definition->key),
        };

        return $field
            ->label($definition->label)
            ->helperText($definition->help)
            ->required($definition->required)
            ->default($definition->default)
            ->rules($definition->rules);
    }
}
