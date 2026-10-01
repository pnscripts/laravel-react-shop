<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PnShop\Catalog\Models\Brand;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Product;
use PnShop\Catalog\Models\ProductAttribute;
use PnShop\Catalog\Models\ProductAttributeValue;
use PnShop\Catalog\Presenters\ProductCardPresenter;
use PnShop\Catalog\Presenters\ProductDetailPresenter;
use PnShop\Catalog\ProductBrowser;
use PnShop\Seo\CatalogSeo;

class ShopController extends Controller
{
    public function index(Request $request): Response
    {
        $browser = ProductBrowser::fromInput($request->only(['category', 'brand', 'filter']));
        $category = $browser->category;
        $brand = $browser->brand;
        $base = $browser->base();

        $products = $browser->query()
            ->with(ProductCardPresenter::RELATIONS)
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product) => ProductCardPresenter::present($product));

        $trail = $category ? Category::query()->whereAncestorOf($category, andSelf: true)->defaultOrder()->get()->all() : [];
        app(CatalogSeo::class)->listing($request, $category, $brand, $trail);

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
                'category_path' => array_map(fn (Category $item) => $item->slug, $trail),
                'brand' => $brand?->slug,
                'attributes' => (object) $browser->attributeFilters,
            ],
        ]);
    }

    public function show(Request $request, Product $product): Response
    {
        abort_unless($product->is_active, 404);

        ProductDetailPresenter::load($product);

        abort_if($product->variants->isEmpty(), 404);

        $trail = ProductDetailPresenter::trail($product);
        app(CatalogSeo::class)->product($request, $product, $trail);

        return Inertia::render('shop/show', [
            'product' => ProductDetailPresenter::present($product, $trail),
            'related' => ProductDetailPresenter::related($product),
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
