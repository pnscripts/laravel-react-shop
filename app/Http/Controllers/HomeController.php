<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $products = Product::query()
            ->active()
            ->with('category:id,title,slug')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Product $product) => $this->productCard($product));

        return Inertia::render('home', [
            'products' => $products,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function productCard(Product $product): array
    {
        return [
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
        ];
    }
}
