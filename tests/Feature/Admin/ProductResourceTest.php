<?php

namespace Tests\Feature\Admin;

use Livewire\Livewire;
use PnShop\Catalog\Filament\Resources\Products\Pages\CreateProduct;
use PnShop\Catalog\Filament\Resources\Products\Pages\EditProduct;
use PnShop\Catalog\Filament\Resources\Products\Pages\ListProducts;
use PnShop\Catalog\Models\Category;
use PnShop\Catalog\Models\Product;
use PnShop\Inventory\InventoryService;

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
        $category = Category::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'title' => 'Desk Lamp',
                'product_category_id' => $category->id,
                'price' => 50,
                'sale_price' => 40,
                'stock' => 7,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->where('slug', 'desk-lamp')->sole();
        $this->assertSame(7, $product->stock);
        $this->assertSame('50.00', (string) $product->price->getAmount());
        $this->assertSame('40.00', (string) $product->sale_price->getAmount());
        $this->assertDatabaseHas('stock_movements', ['product_variant_id' => $product->defaultVariant()->id, 'quantity' => 7, 'reason' => 'adjustment']);
    }

    public function test_discount_must_be_below_the_price(): void
    {
        $this->actingAsAdministrator();
        $product = Product::factory()->create(['price' => 100, 'sale_price' => null]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['sale_price' => 120])
            ->call('save')
            ->assertHasFormErrors(['sale_price']);

        $this->assertNull($product->fresh()->sale_price);
    }

    public function test_saving_a_product_keeps_stock_reserved_for_orders(): void
    {
        $this->actingAsAdministrator();
        $product = Product::factory()->create(['price' => 10, 'sale_price' => null, 'stock' => 5]);
        app(InventoryService::class)->reserve($product->defaultVariant(), 2);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSchemaStateSet(['stock' => 5])
            ->fillForm(['price' => 12])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['on_hand' => 5, 'reserved' => 2], $product->defaultVariant()->stockLevels()->sole()->only(['on_hand', 'reserved']));
        $this->assertSame(3, $product->fresh()->stock);
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
