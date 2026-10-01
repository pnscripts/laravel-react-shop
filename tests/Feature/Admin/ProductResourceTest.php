<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductCategory;
use Livewire\Livewire;
use PnShop\Catalog\Filament\Resources\Products\Pages\CreateProduct;
use PnShop\Catalog\Filament\Resources\Products\Pages\EditProduct;
use PnShop\Catalog\Filament\Resources\Products\Pages\ListProducts;

class ProductResourceTest extends AdminTestCase
{
    public function test_products_are_listed(): void
    {
        $this->actingAsAdministrator();
        $products = Product::factory()->count(3)->create();

        Livewire::test(ListProducts::class)->assertCanSeeTableRecords($products);
    }

    public function test_a_product_can_be_created(): void
    {
        $this->actingAsAdministrator();
        $category = ProductCategory::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'title' => 'Desk Lamp',
                'product_category_id' => $category->id,
                'price' => 50,
                'discount_price' => 40,
                'stock' => 7,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', ['title' => 'Desk Lamp', 'slug' => 'desk-lamp', 'stock' => 7]);
    }

    public function test_discount_must_be_below_the_price(): void
    {
        $this->actingAsAdministrator();
        $product = Product::factory()->create(['price' => 100, 'discount_price' => null]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['discount_price' => 120])
            ->call('save')
            ->assertHasFormErrors(['discount_price']);

        $this->assertNull($product->fresh()->discount_price);
    }

    public function test_editing_is_logged(): void
    {
        $admin = $this->actingAsAdministrator();
        $product = Product::factory()->create(['stock' => 3]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['stock' => 9])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'catalog',
            'subject_id' => $product->id,
            'causer_id' => $admin->id,
            'causer_type' => 'admin_user',
        ]);
    }

    public function test_staff_without_permission_cannot_create(): void
    {
        $this->actingAsStaff(['catalog.products.view']);

        Livewire::test(ListProducts::class)->assertActionHidden('create');
        $this->get('/admin/products/create')->assertForbidden();
    }

    public function test_translations_are_saved_and_shown_on_the_localized_storefront(): void
    {
        $this->actingAsAdministrator();
        $product = Product::factory()->active()->create(['title' => 'Desk Lamp']);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['translations' => ['bg' => ['title' => 'Настолна лампа', 'slug' => '', 'description' => 'Ярка']]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Настолна лампа', $product->translation('title', 'bg'));
        $this->assertSame('nastolna-lampa', $product->translation('slug', 'bg'));
        $this->assertSame('Desk Lamp', $product->fresh()->getRawOriginal('title'));

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSchemaStateSet(['translations.bg.title' => 'Настолна лампа']);

        $this->get('/bg/shop/nastolna-lampa')->assertOk();
    }
}
