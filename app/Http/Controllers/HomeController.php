<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use PnShop\Catalog\Models\Product;
use PnShop\Money\MoneyPresenter;

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
            'price' => MoneyPresenter::present($product->price),
            'discount_price' => MoneyPresenter::present($product->discount_price),
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
