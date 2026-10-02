<?php

namespace PnShop\Catalog\Filament\Resources\Options;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PnShop\Catalog\Filament\Resources\Options\Pages\CreateOption;
use PnShop\Catalog\Filament\Resources\Options\Pages\EditOption;
use PnShop\Catalog\Filament\Resources\Options\Pages\ListOptions;
use PnShop\Catalog\Models\Option;
use PnShop\Catalog\Models\OptionValue;
use PnShop\Localization\Filament\TranslationsSection;
use PnShop\Localization\Localization;
use PnShop\Localization\Models\Language;
use UnitEnum;

/**
 * Variant options (Size, Color, ...) and their values.
 */
class OptionResource extends Resource
{
    protected static ?string $model = Option::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $navigationLabel = 'Variant options';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        $languages = app(Localization::class)->languages()->reject(fn (Language $language) => $language->is_default);

        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('code')
                    ->required()
                    ->maxLength(64)
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->helperText('Internal identifier, e.g. size or color.'),
            ]),
            TranslationsSection::make([
                'name' => fn (string $name) => TextInput::make($name)->label('Name')->maxLength(255),
            ]),
            Section::make('Values')->schema([
                Repeater::make('values')
                    ->hiddenLabel()
                    ->relationship()
                    ->orderColumn('position')
                    ->columns(1 + $languages->count())
                    ->mutateRelationshipDataBeforeFillUsing(fn (array $data) => [
                        ...$data,
                        'translations_input' => OptionValue::query()->whereKey((int) $data['id'])->first()?->translationsInput() ?? [],
                    ])
                    ->schema([
                        TextInput::make('value')->required()->maxLength(255),
                        ...$languages->map(fn (Language $language) => TextInput::make("translations_input.{$language->code}.value")
                            ->label($language->native_name)
                            ->maxLength(255))->all(),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('code')->color('gray'),
                TextColumn::make('values.value')->label('Values')->badge()->limitList(8),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOptions::route('/'),
            'create' => CreateOption::route('/create'),
            'edit' => EditOption::route('/{record}/edit'),
        ];
    }
}
