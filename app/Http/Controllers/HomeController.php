<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use PnShop\Catalog\Models\Product;
use PnShop\Catalog\Presenters\ProductCardPresenter;
use PnShop\Cms\ContentRenderer;
use PnShop\Cms\Models\Page;

class HomeController extends Controller
{
    public function __invoke(ContentRenderer $content): Response
    {
        // A page marked as the homepage replaces the default home.
        $home = Page::query()->live()->where('is_home', true)->first();

        if ($home !== null) {
            return Inertia::render('home', ['products' => [], 'blocks' => $content->render($home), 'title' => $home->title]);
        }

        $products = Product::query()
            ->active()
            ->with(ProductCardPresenter::RELATIONS)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Product $product) => ProductCardPresenter::present($product));

        return Inertia::render('home', [
            'products' => $products,
            'blocks' => null,
            'title' => null,
        ]);
    }
}
