<?php

namespace PnShop\Catalog;

use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use PnShop\Catalog\Policies\ProductPolicy;
use PnShop\Foundation\Extension\Permission;
use PnShop\Foundation\ModuleServiceProvider;

/**
 * Catalog module. The catalog models still live in app/Models; they move into
 * this module with the catalog redesign (products, variants, attributes).
 */
class CatalogServiceProvider extends ModuleServiceProvider
{
    protected function permissions(): array
    {
        return [
            new Permission('catalog.products.view', 'View products', 'Catalog'),
            new Permission('catalog.products.create', 'Create products', 'Catalog'),
            new Permission('catalog.products.update', 'Edit products', 'Catalog'),
            new Permission('catalog.products.delete', 'Delete products', 'Catalog'),
        ];
    }

    protected function bootModule(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);
    }
}
