<?php

namespace PnShop\Catalog\Filament\Resources\Attributes;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PnShop\Catalog\Filament\Resources\Attributes\Pages\CreateAttribute;
use PnShop\Catalog\Filament\Resources\Attributes\Pages\EditAttribute;
use PnShop\Catalog\Filament\Resources\Attributes\Pages\ListAttributes;
use PnShop\Catalog\Filament\Resources\Categories\CategoryResource;
use PnShop\Catalog\Models\ProductAttribute;
use PnShop\Catalog\Models\ProductAttributeValue;
use PnShop\Localization\Filament\TranslationsSection;
use PnShop\Localization\Localization;
use PnShop\Localization\Models\Language;
use UnitEnum;

/**
 * Specification attributes (material, power, Wi-Fi, ...): shown on product pages
 * and, when filterable, offered as shop filters.
 */
class AttributeResource extends Resource
{
    protected static ?string $model = ProductAttribute::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $navigationLabel = 'Attributes';

    protected static ?string $modelLabel = 'attribute';

    protected static ?int $navigationSort = 50;

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Schema $schema): Schema
    {
        $languages = app(Localization::class)->languages()->reject(fn (Language $language) => $language->is_default);

        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('label')->required()->maxLength(255),
                TextInput::make('key')->required()->maxLength(64)->alphaDash()->unique(ignoreRecord: true),
                Select::make('type')
                    ->options(['select' => 'One value per product', 'multiselect' => 'Several values per product', 'boolean' => 'Yes / no'])
                    ->default('select')
                    ->required(),
                Select::make('categories')
                    ->label('Shown for categories')
                    ->relationship('categories', 'title')
                    ->options(fn () => CategoryResource::parentOptions(null))
                    ->multiple()
                    ->searchable()
                    ->helperText('Products in these categories show this attribute.'),
                Toggle::make('is_filterable')->label('Offer as a shop filter'),
                Toggle::make('is_required')->label('Required'),
            ]),
            TranslationsSection::make([
                'label' => fn (string $name) => TextInput::make($name)->label('Label')->maxLength(255),
            ]),
            Section::make('Values')->schema([
                Repeater::make('values')
                    ->hiddenLabel()
                    ->relationship()
                    ->orderColumn('position')
                    ->columns(1 + $languages->count())
                    ->mutateRelationshipDataBeforeFillUsing(fn (array $data) => [
                        ...$data,
                        'translations_input' => ProductAttributeValue::query()->whereKey((int) $data['id'])->first()?->translationsInput() ?? [],
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
                TextColumn::make('label')->searchable(),
                TextColumn::make('key')->color('gray'),
                TextColumn::make('values.value')->label('Values')->badge()->limitList(6),
                IconColumn::make('is_filterable')->label('Filter')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttributes::route('/'),
            'create' => CreateAttribute::route('/create'),
            'edit' => EditAttribute::route('/{record}/edit'),
        ];
    }
}
