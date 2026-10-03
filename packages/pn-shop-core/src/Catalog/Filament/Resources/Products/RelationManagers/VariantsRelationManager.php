<?php

namespace PnShop\Catalog\Filament\Resources\Products\RelationManagers;

use Brick\Money\Money;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PnShop\Catalog\Filament\Resources\Products\Schemas\ProductForm;
use PnShop\Catalog\Models\Option;
use PnShop\Catalog\Models\OptionValue;
use PnShop\Catalog\Models\Product;
use PnShop\Catalog\Models\ProductVariant;
use PnShop\Catalog\ProductType;
use PnShop\Inventory\InventoryService;
use PnShop\Inventory\Models\StockLevel;

/**
 * Variants of a product with options: one per combination of option values.
 */
class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variants';

    /** Variants are the heart of a variable product's edit page; render them with the page. */
    protected static bool $isLazy = false;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Product && $ownerRecord->type === ProductType::Variable;
    }

    public function form(Schema $schema): Schema
    {
        /** @var Product $product */
        $product = $this->getOwnerRecord();

        return $schema->components([
            Grid::make(max(1, $product->options->count()))->schema(
                $product->options->map(fn (Option $option) => Select::make("option_{$option->id}")
                    ->label($option->name)
                    ->options($option->values->pluck('value', 'id'))
                    ->required()
                )->all(),
            )->columnSpanFull(),
            TextInput::make('price')->numeric()->minValue(0)->required(),
            TextInput::make('sale_price')->label('Sale price')->numeric()->minValue(0)->lt('price'),
            TextInput::make('sku')
                ->label('SKU')
                ->maxLength(255)
                ->unique('product_variants', 'sku', ignoreRecord: true),
            TextInput::make('barcode')->maxLength(255),
            TextInput::make('weight')->label('Weight (grams)')->integer()->minValue(0),
            TextInput::make('stock')->label('Stock on hand')->integer()->minValue(0)->default(0)
                ->disabled(fn () => ! ProductForm::canManageStock())
                ->dehydrated(fn () => ProductForm::canManageStock())
                ->helperText('Changes are recorded in the stock history.'),
            Toggle::make('is_active')->label('Available')->default(true),
            Toggle::make('track_inventory')->label('Track stock')->default(true),
            Toggle::make('allow_backorder')->label('Sell when out of stock'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['optionValues', 'stockLevels']))
            ->reorderable('position')
            ->columns([
                TextColumn::make('label')
                    ->label('Variant')
                    ->state(fn (ProductVariant $record) => $record->label() ?: '—'),
                TextColumn::make('sku')->label('SKU')->placeholder('—'),
                TextColumn::make('price')->formatStateUsing(fn (?Money $state) => $state?->formatToLocale(app()->getLocale())),
                TextColumn::make('sale_price')->label('Sale')->formatStateUsing(fn (?Money $state) => $state?->formatToLocale(app()->getLocale()))->placeholder('—'),
                TextColumn::make('available')
                    ->label('Stock')
                    ->state(fn (ProductVariant $record) => $record->available() ?? '∞')
                    ->color(fn (mixed $state) => $state === 0 ? 'danger' : null),
                IconColumn::make('is_default')->label('Default')->boolean(),
                IconColumn::make('is_active')->label('Available')->boolean(),
            ])
            ->headerActions([
                Action::make('generate')
                    ->label('Generate variants')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->requiresConfirmation()
                    ->modalDescription('Creates one variant for every combination of option values that does not exist yet, priced like the default variant.')
                    ->action(fn () => $this->generateVariants()),
                CreateAction::make()
                    ->using(fn (array $data) => $this->saveVariant(new ProductVariant(['product_id' => $this->getOwnerRecord()->getKey()]), $data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, ProductVariant $record) => $this->fillVariantData($data, $record))
                    ->using(fn (ProductVariant $record, array $data) => $this->saveVariant($record, $data)),
                DeleteAction::make(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function fillVariantData(array $data, ProductVariant $record): array
    {
        foreach ($record->optionValues as $value) {
            $data["option_{$value->option_id}"] = $value->id;
        }

        $data['price'] = (string) $record->price->getAmount();
        $data['sale_price'] = $record->sale_price !== null ? (string) $record->sale_price->getAmount() : null;
        $data['stock'] = (int) $record->stockLevels->sum(fn (StockLevel $level) => $level->on_hand);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveVariant(ProductVariant $variant, array $data): ProductVariant
    {
        /** @var Product $product */
        $product = $this->getOwnerRecord();
        $valueIds = $product->options->map(fn (Option $option) => (int) $data["option_{$option->id}"])->sort()->values()->all();

        $duplicate = $product->variants()->whereKeyNot($variant->getKey() ?? 0)->with('optionValues')->get()
            ->first(fn (ProductVariant $other) => $other->optionValues->modelKeys() !== [] && collect($other->optionValues->modelKeys())->sort()->values()->all() === $valueIds);

        if ($duplicate !== null) {
            Notification::make()->danger()->title('A variant with these options already exists.')->send();

            throw new Halt;
        }

        return DB::transaction(function () use ($variant, $data, $valueIds) {
            $variant->fill(collect($data)->only(['price', 'sale_price', 'sku', 'barcode', 'weight', 'is_active', 'track_inventory', 'allow_backorder'])->all())->save();
            $variant->optionValues()->sync($valueIds);

            if (array_key_exists('stock', $data) && ProductForm::canManageStock()) {
                app(InventoryService::class)->setOnHand($variant, (int) $data['stock'], auth('admin')->user());
            }

            return $variant;
        });
    }

    private function generateVariants(): void
    {
        /** @var Product $product */
        $product = $this->getOwnerRecord();
        $product->load(['options.values', 'variants.optionValues']);

        $combinations = [[]];

        foreach ($product->options as $option) {
            $combinations = collect($combinations)
                ->flatMap(fn (array $combination) => $option->values->map(fn (OptionValue $value) => [...$combination, $value->id]))
                ->all();
        }

        $existing = $product->variants->map(fn (ProductVariant $variant) => collect($variant->optionValues->modelKeys())->sort()->implode('-'));
        $template = $product->defaultVariant();
        $created = 0;

        foreach ($combinations as $combination) {
            sort($combination);

            if ($combination === [] || $existing->contains(implode('-', $combination))) {
                continue;
            }

            $variant = ProductVariant::query()->create([
                'product_id' => $product->id,
                'price' => $template->price ?? 0,
                'sale_price' => $template?->sale_price,
            ]);
            $variant->optionValues()->sync($combination);
            $created++;
        }

        Notification::make()->success()->title("{$created} variant(s) created.")->send();
    }
}
