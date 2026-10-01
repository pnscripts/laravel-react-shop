<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $products = Product::query()
            ->with('category:id,title')
            ->latest()
            ->paginate(15)
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'price' => $product->price,
                'stock' => $product->stock,
                'is_active' => $product->is_active,
                'category' => $product->category?->title,
            ]);

        return Inertia::render('admin/products/index', [
            'products' => $products,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/products/form', [
            'product' => null,
            'categories' => $this->categories(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('admin/products/form', [
            'product' => [
                'id' => $product->id,
                'product_category_id' => $product->product_category_id,
                'title' => $product->title,
                'description' => $product->description,
                'price' => $product->price,
                'discount_price' => $product->discount_price,
                'stock' => $product->stock,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'image' => $product->image,
                'is_active' => $product->is_active,
            ],
            'categories' => $this->categories(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function deactivate(Product $product): RedirectResponse
    {
        $product->update(['is_active' => false]);

        return back()->with('success', 'Product deactivated.');
    }

    /**
     * @return Collection<int, ProductCategory>
     */
    private function categories(): Collection
    {
        return ProductCategory::query()
            ->orderBy('title')
            ->get(['id', 'title']);
    }
}
