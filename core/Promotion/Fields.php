<?php

namespace PnShop\Promotion;

use Filament\Forms\Components\Select;
use PnShop\Catalog\Filament\Resources\Categories\CategoryResource;
use PnShop\Catalog\Models\Product;

/**
 * Admin form fields shared by condition and action types.
 */
final class Fields
{
    public static function products(string $label = 'Products'): Select
    {
        return Select::make('product_ids')
            ->label($label)
            ->multiple()
            ->searchable()
            ->getSearchResultsUsing(fn (string $search) => Product::query()
                ->whereLike('title', '%'.addcslashes($search, '%_\\').'%')
                ->limit(50)
                ->pluck('title', 'id')
                ->all())
            ->getOptionLabelsUsing(fn (array $values) => Product::query()->whereKey($values)->pluck('title', 'id')->all());
    }

    public static function categories(string $label = 'Categories'): Select
    {
        return Select::make('category_ids')
            ->label($label)
            ->helperText('Includes their subcategories.')
            ->multiple()
            ->searchable()
            ->options(fn () => CategoryResource::parentOptions(null));
    }

    /**
     * Rules for the optional product/category scope.
     *
     * @return array<string, mixed>
     */
    public static function scopeRules(): array
    {
        return [
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer'],
        ];
    }
}
