<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use PnShop\Catalog\Models\Product;
use PnShop\Catalog\Presenters\ProductCardPresenter;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $products = Product::query()
            ->active()
            ->with(ProductCardPresenter::RELATIONS)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Product $product) => ProductCardPresenter::present($product));

        return Inertia::render('home', [
            'products' => $products,
        ]);
    }
}
