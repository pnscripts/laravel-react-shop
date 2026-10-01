<?php

namespace PnShop\Catalog;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Product;
use PnShop\Catalog\Models\ProductAttribute;
use PnShop\Catalog\Models\ProductAttributeValue;
use PnShop\Catalog\Policies\ProductPolicy;
use PnShop\Foundation\Extension\Permission;
use PnShop\Foundation\ModuleServiceProvider;

/**
 * Catalog module: products, categories, attributes and their admin screens.
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

    public function register(): void
    {
        Relation::morphMap([
            'product' => Product::class,
            'category' => Category::class,
            'product_attribute' => ProductAttribute::class,
            'product_attribute_value' => ProductAttributeValue::class,
        ]);
    }

    protected function bootModule(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);
    }
}
