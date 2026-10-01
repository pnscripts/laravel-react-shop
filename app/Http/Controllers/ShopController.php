<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PnShop\Catalog\Models\Brand;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Product;
use PnShop\Catalog\Presenters\ProductCardPresenter;
use PnShop\Money\MoneyPresenter;

class ShopController extends Controller
{
    public function index(Request $request): Response
    {
        $categorySlug = $request->string('category')->toString();
        $brandSlug = $request->string('brand')->toString();

        $category = $categorySlug !== '' ? Category::query()->active()->whereTranslated('slug', $categorySlug)->first() : null;
        $brand = $brandSlug !== '' ? Brand::query()->active()->whereTranslated('slug', $brandSlug)->first() : null;

        $products = Product::query()
            ->active()
            ->with('category:id,title,slug')
            ->when($categorySlug !== '', fn (Builder $query) => $category
                ? $query->whereHas('categories', fn (Builder $categories) => $categories->whereKey($category->subtreeIds()))
                : $query->whereRaw('1 = 0'))
            ->when($brandSlug !== '', fn (Builder $query) => $query->where('brand_id', $brand->id ?? 0))
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
            'filters' => [
                'category' => $category?->slug,
                'category_path' => $category ? Category::query()->whereAncestorOf($category, andSelf: true)->defaultOrder()->pluck('slug') : [],
                'brand' => $brand?->slug,
            ],
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load(['category:id,title,slug,parent_id,_lft,_rgt', 'brand:id,name,slug', 'selectedAttributeValues']);

        $breadcrumbs = $product->category
            ? Category::query()->whereAncestorOf($product->category, andSelf: true)->defaultOrder()->get(['id', 'title', 'slug', '_lft', '_rgt'])
                ->map(fn (Category $category) => ['title' => $category->title, 'slug' => $category->slug])
                ->values()
            : [];

        return Inertia::render('shop/show', [
            'product' => [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => MoneyPresenter::present($product->price),
                'discount_price' => MoneyPresenter::present($product->discount_price),
                'image' => $product->image,
                'stock' => $product->stock,
                'sku' => $product->sku,
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
            ],
        ]);
    }
}
