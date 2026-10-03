<?php

namespace PnShop\Cms\Filament\Resources\Menus\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use PnShop\Catalog\Filament\Resources\Categories\CategoryResource;
use PnShop\Catalog\Models\Brand;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Product;
use PnShop\Cms\MenuItemType;
use PnShop\Cms\Models\MenuItem;
use PnShop\Cms\Models\Page;
use PnShop\Localization\Localization;
use PnShop\Localization\Models\Language;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Items';

    protected static bool $isLazy = false;

    public function form(Schema $schema): Schema
    {
        $type = fn (Get $get): ?MenuItemType => $get('type') instanceof MenuItemType ? $get('type') : MenuItemType::tryFrom((string) $get('type'));
        $languages = app(Localization::class)->languages()->reject(fn (Language $language) => $language->is_default);

        return $schema->columns(2)->components([
            Select::make('type')->options(MenuItemType::class)->required()->live()->default(MenuItemType::Page->value),
            Select::make('target_id')
                ->label(fn (Get $get) => $type($get)?->getLabel() ?? 'Target')
                ->searchable()
                ->options(fn (Get $get) => match ($type($get)) {
                    MenuItemType::Page => Page::query()->orderBy('title')->pluck('title', 'id')->all(),
                    MenuItemType::Category => CategoryResource::parentOptions(null),
                    MenuItemType::Brand => Brand::query()->orderBy('name')->pluck('name', 'id')->all(),
                    MenuItemType::Product => Product::query()->latest()->limit(500)->pluck('title', 'id')->all(),
                    default => [],
                })
                ->visible(fn (Get $get) => $type($get)?->hasTarget() ?? false)
                ->required(fn (Get $get) => $type($get)?->hasTarget() ?? false),
            TextInput::make('url')->label('Address')->maxLength(2048)
                ->rules(['regex:#^(https?://|mailto:|tel:|/(?![/\\\\]))#i'])
                ->helperText('A page of this shop (/shop/…) or a full address.')
                ->visible(fn (Get $get) => $type($get) === MenuItemType::Url)
                ->required(fn (Get $get) => $type($get) === MenuItemType::Url),
            TextInput::make('label')->maxLength(100)
                ->helperText('Leave empty to use the name of the page, category, brand or product.')
                ->required(fn (Get $get) => in_array($type($get), [MenuItemType::Url, MenuItemType::Heading], true)),
            Select::make('parent_id')->label('Inside')
                ->placeholder('Top level')
                ->options(fn (?MenuItem $record) => MenuItem::query()
                    ->where('menu_id', $this->getOwnerRecord()->getKey())
                    ->whereNull('parent_id')
                    ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                    ->get()
                    ->mapWithKeys(fn (MenuItem $item) => [$item->id => $item->label ?: $item->type->getLabel()])
                    ->all()),
            TextInput::make('position')->integer()->default(0),
            Toggle::make('new_tab')->label('Open in a new tab'),
            ...$languages->map(fn (Language $language) => TextInput::make("translations.{$language->code}.label")
                ->label("Label ({$language->native_name})")
                ->maxLength(100)
                ->dehydrated(false)
                ->afterStateHydrated(fn (TextInput $component, ?MenuItem $record) => $component->state($record?->translations()->where('locale', $language->code)->value('label')))
                ->saveRelationshipsUsing(fn (MenuItem $record, ?string $state) => $record->setTranslations($language->code, ['label' => $state])))->values()->all(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('parent'))
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('label')
                    ->state(fn (MenuItem $record) => ($record->parent_id ? '— ' : '').($record->label ?: $this->targetName($record)))
                    ->description(fn (MenuItem $record) => $record->type->getLabel()),
                TextColumn::make('parent.label')->label('Inside')->placeholder('Top level'),
                IconColumn::make('new_tab')->label('New tab')->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    private function targetName(MenuItem $item): string
    {
        $model = match ($item->type) {
            MenuItemType::Page => Page::query()->find($item->target_id),
            MenuItemType::Brand => Brand::query()->find($item->target_id),
            MenuItemType::Product => Product::query()->find($item->target_id),
            MenuItemType::Category => Category::query()->find($item->target_id),
            default => null,
        };

        return $model instanceof Model ? (string) ($model->getAttribute('title') ?? $model->getAttribute('name')) : $item->type->getLabel();
    }
}
