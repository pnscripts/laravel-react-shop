<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(Request $request): Response
    {
        $categorySlug = $request->string('category')->toString();

        $products = Product::query()
            ->active()
            ->with('category:id,title,slug')
            ->when($categorySlug, function ($query) use ($categorySlug) {
                $query->whereHas('category', fn ($category) => $category->whereTranslated('slug', $categorySlug));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'price' => $product->price,
                'discount_price' => $product->discount_price,
                'image' => $product->image,
                'stock' => $product->stock,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'title' => $product->category->title,
                    'slug' => $product->category->slug,
                ] : null,
            ]);

        $categories = ProductCategory::query()
            ->whereActive()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title', 'slug']);

        return Inertia::render('shop/index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'category' => $categorySlug ?: null,
            ],
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load(['category:id,title,slug', 'selectedAttributeValues']);

        return Inertia::render('shop/show', [
            'product' => [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => $product->price,
                'discount_price' => $product->discount_price,
                'image' => $product->image,
                'stock' => $product->stock,
                'sku' => $product->sku,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'title' => $product->category->title,
                    'slug' => $product->category->slug,
                ] : null,
                'attributes' => $product->category
                    ? $product->getProductAttributesWithValues()
                    : [],
            ],
        ]);
    }
}
