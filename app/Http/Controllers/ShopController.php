<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PnShop\Catalog\Models\Brand;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Option;
use PnShop\Catalog\Models\OptionValue;
use PnShop\Catalog\Models\Product;
use PnShop\Catalog\Models\ProductAttribute;
use PnShop\Catalog\Models\ProductAttributeValue;
use PnShop\Catalog\Models\ProductVariant;
use PnShop\Catalog\Presenters\ProductCardPresenter;
use PnShop\Media\MediaPresenter;
use PnShop\Media\Models\Media;
use PnShop\Money\MoneyPresenter;

class ShopController extends Controller
{
    public function index(Request $request): Response
    {
        $categorySlug = $request->string('category')->toString();
        $brandSlug = $request->string('brand')->toString();

        $category = $categorySlug !== '' ? Category::query()->active()->whereTranslated('slug', $categorySlug)->first() : null;
        $brand = $brandSlug !== '' ? Brand::query()->active()->whereTranslated('slug', $brandSlug)->first() : null;

        /** @var array<int, list<int>> $attributeFilters attribute id => chosen value ids */
        $attributeFilters = collect((array) $request->input('filter', []))
            ->mapWithKeys(fn (mixed $values, mixed $attributeId) => [(int) $attributeId => array_values(array_filter(array_map('intval', (array) $values)))])
            ->filter()
            ->all();

        $base = Product::query()
            ->active()
            ->when($categorySlug !== '', fn (Builder $query) => $category
                ? $query->whereHas('categories', fn (Builder $categories) => $categories->whereKey($category->subtreeIds()))
                : $query->whereRaw('1 = 0'))
            ->when($brandSlug !== '', fn (Builder $query) => $query->where('brand_id', $brand->id ?? 0));

        $products = (clone $base)
            ->with(ProductCardPresenter::RELATIONS)
            // Values of one attribute are alternatives (OR); different attributes narrow down (AND).
            ->tap(function (Builder $query) use ($attributeFilters): void {
                foreach ($attributeFilters as $valueIds) {
                    $query->whereHas('selectedAttributeValues', fn (Builder $values) => $values->whereIn('product_attribute_values.id', $valueIds));
                }
            })
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product) => ProductCardPresenter::present($product));

        $categories = Category::query()->active()->defaultOrder()->get(['id', 'title', 'slug', 'parent_id', '_lft', '_rgt']);
        $link = fn (Category $category): array => ['id' => $category->id, 'title' => $category->title, 'slug' => $category->slug];

        return Inertia::render('shop/index', [
            'products' => $products,
            'categories' => $categories->whereNull('parent_id')->map(fn (Category $root): array => [
                ...$link($root),
                'children' => $categories->where('parent_id', $root->id)->map($link)->values()->all(),
            ])->values()->all(),
            'brands' => Brand::query()->active()->orderBy('name')->get(['id', 'name', 'slug']),
            'facets' => $this->facets($base),
            'filters' => [
                'category' => $category?->slug,
                'category_path' => $category ? Category::query()->whereAncestorOf($category, andSelf: true)->defaultOrder()->pluck('slug') : [],
                'brand' => $brand?->slug,
                'attributes' => (object) $attributeFilters,
            ],
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category:id,title,slug,parent_id,_lft,_rgt',
            'brand:id,name,slug',
            'selectedAttributeValues',
            'media',
            'options.values',
            'variants' => fn ($variants) => $variants->where('is_active', true)->with(['optionValues', 'stockLevels']),
        ]);

        abort_if($product->variants->isEmpty(), 404);

        $breadcrumbs = $product->category
            ? Category::query()->whereAncestorOf($product->category, andSelf: true)->defaultOrder()->get(['id', 'title', 'slug', '_lft', '_rgt'])
                ->map(fn (Category $category) => ['title' => $category->title, 'slug' => $category->slug])
                ->values()
            : [];

        $usedValueIds = $product->variants->flatMap(fn (ProductVariant $variant) => $variant->optionValues->modelKeys())->unique();

        return Inertia::render('shop/show', [
            'product' => [
                'id' => $product->id,
                'type' => $product->type->value,
                'title' => $product->title,
                'slug' => $product->slug,
                'description' => $product->description,
                'image' => ProductCardPresenter::mainImage($product),
                'gallery' => $product->mediaIn('gallery')->map(fn (Media $media) => MediaPresenter::present($media, $product->title))->values()->all(),
                'brand' => $product->brand ? ['name' => $product->brand->name, 'slug' => $product->brand->slug] : null,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'title' => $product->category->title,
                    'slug' => $product->category->slug,
                ] : null,
                'breadcrumbs' => $breadcrumbs,
                'attributes' => $product->category
                    ? $product->getProductAttributesWithValues()
                    : [],
                'options' => $product->options->map(fn (Option $option) => [
                    'id' => $option->id,
                    'name' => $option->name,
                    'values' => $option->values
                        ->filter(fn (OptionValue $value) => $usedValueIds->contains($value->id))
                        ->map(fn (OptionValue $value) => ['id' => $value->id, 'value' => $value->value])
                        ->values(),
                ])->values(),
                'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'option_value_ids' => $variant->optionValues->modelKeys(),
                    'price' => MoneyPresenter::present($variant->price),
                    'sale_price' => $variant->isOnSale() ? MoneyPresenter::present($variant->sale_price) : null,
                    'stock' => $variant->available(),
                    'can_backorder' => $variant->allow_backorder,
                ])->values(),
                'default_variant_id' => $product->defaultVariant()?->id,
            ],
        ]);
    }

    /**
     * Filterable attributes with the values that occur among the products being browsed.
     *
     * @param  Builder<Product>  $base
     * @return list<array{id: int, label: string, values: list<array{id: int, value: string}>}>
     */
    private function facets(Builder $base): array
    {
        $productIds = (clone $base)->select('products.id');

        return ProductAttribute::query()
            ->where('is_filterable', true)
            ->orderBy('position')
            ->with(['values' => fn ($values) => $values->whereHas('products', fn (Builder $products) => $products->whereIn('products.id', $productIds))])
            ->get()
            ->filter(fn (ProductAttribute $attribute) => $attribute->values->isNotEmpty())
            ->map(fn (ProductAttribute $attribute) => [
                'id' => $attribute->id,
                'label' => $attribute->label,
                'values' => $attribute->values->map(fn (ProductAttributeValue $value) => ['id' => $value->id, 'value' => $value->value])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
