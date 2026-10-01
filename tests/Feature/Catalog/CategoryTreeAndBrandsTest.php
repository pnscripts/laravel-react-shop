<?php

namespace Tests\Feature\Catalog;

use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use PnShop\Catalog\Filament\Resources\Categories\CategoryResource;
use PnShop\Catalog\Filament\Resources\Categories\Pages\CreateCategory;
use PnShop\Catalog\Filament\Resources\Categories\Pages\ListCategories;
use PnShop\Catalog\Filament\Resources\Products\Pages\EditProduct;
use PnShop\Catalog\Models\Brand;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Product;
use Tests\Feature\Admin\AdminTestCase;

class CategoryTreeAndBrandsTest extends AdminTestCase
{
    public function test_a_category_lists_products_from_its_whole_subtree(): void
    {
        $lighting = Category::create(['title' => 'Lighting']);
        $lamps = Category::create(['title' => 'Lamps', 'parent_id' => $lighting->id]);
        $other = Category::create(['title' => 'Furniture']);

        $lamp = Product::factory()->active()->create(['product_category_id' => $lamps->id]);
        $chair = Product::factory()->active()->create(['product_category_id' => $other->id]);
        $chair->categories()->attach($lighting->id);
        Product::factory()->active()->create(['product_category_id' => $other->id]);

        $this->get('/shop?category=lighting')->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 2)
            ->where('filters.category_path', ['lighting'])
            ->where('categories.0.slug', 'lighting')
            ->where('categories.0.children.0.slug', 'lamps')
        );

        $this->get('/shop?category=lamps')->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.id', $lamp->id)
            ->where('filters.category_path', ['lighting', 'lamps'])
        );

        $this->get('/shop?category=missing')->assertInertia(fn (Assert $page) => $page->has('products.data', 0));
    }

    public function test_products_can_be_filtered_by_brand(): void
    {
        $brand = Brand::create(['name' => 'Lumen Works']);
        Product::factory()->active()->create(['brand_id' => $brand->id]);
        Product::factory()->active()->create();

        $this->get('/shop?brand=lumen-works')->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('brands.0.slug', 'lumen-works')
        );
    }

    public function test_product_pages_show_the_category_path_and_brand(): void
    {
        $lighting = Category::create(['title' => 'Lighting']);
        $lamps = Category::create(['title' => 'Lamps', 'parent_id' => $lighting->id]);
        $brand = Brand::create(['name' => 'Lumen Works']);
        $product = Product::factory()->active()->create(['product_category_id' => $lamps->id, 'brand_id' => $brand->id]);

        $this->get("/shop/{$product->slug}")->assertInertia(fn (Assert $page) => $page
            ->where('product.breadcrumbs.0.slug', 'lighting')
            ->where('product.breadcrumbs.1.slug', 'lamps')
            ->where('product.brand.name', 'Lumen Works')
        );
    }

    public function test_the_primary_category_is_always_one_of_the_products_categories(): void
    {
        $this->actingAsAdministrator();
        $primary = Category::create(['title' => 'Primary']);
        $extra = Category::create(['title' => 'Extra']);
        $product = Product::factory()->create(['product_category_id' => $primary->id]);

        $this->assertSame([$primary->id], $product->categories()->pluck('product_categories.id')->all());

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['categories' => [$extra->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEqualsCanonicalizing([$primary->id, $extra->id], $product->categories()->pluck('product_categories.id')->all());
    }

    public function test_categories_can_be_nested_and_reordered_in_the_admin(): void
    {
        $this->actingAsAdministrator();
        $lighting = Category::create(['title' => 'Lighting']);

        Livewire::test(CreateCategory::class)
            ->fillForm(['title' => 'Lamps', 'parent_id' => $lighting->id, 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $lamps = Category::query()->where('title', 'Lamps')->sole();
        $this->assertSame($lighting->id, $lamps->parent_id);

        $options = CategoryResource::parentOptions($lighting->fresh());
        $this->assertArrayNotHasKey($lighting->id, $options, 'A category cannot be its own parent.');
        $this->assertArrayNotHasKey($lamps->id, $options, 'A category cannot move under its own descendant.');

        $second = Category::create(['title' => 'Garden']);
        Livewire::test(ListCategories::class)->callTableAction('up', $second);

        $this->assertSame(['Garden', 'Lighting', 'Lamps'], Category::query()->defaultOrder()->pluck('title')->all());
    }

    public function test_category_and_brand_screens_need_their_permissions(): void
    {
        $this->actingAsStaff(['catalog.products.view']);

        $this->get('/admin/categories')->assertForbidden();
        $this->get('/admin/brands')->assertForbidden();
    }

    public function test_catalog_managers_can_manage_categories_and_brands(): void
    {
        $this->actingAsStaff(role: 'catalog-manager');

        $this->get('/admin/categories')->assertOk();
        $this->get('/admin/brands')->assertOk();
    }
}
