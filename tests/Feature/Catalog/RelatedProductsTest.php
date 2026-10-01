<?php

namespace Tests\Feature\Catalog;

use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use PnShop\Catalog\Filament\Resources\Products\Pages\EditProduct;
use PnShop\Catalog\Models\Product;
use Tests\Feature\Admin\AdminTestCase;

class RelatedProductsTest extends AdminTestCase
{
    public function test_related_products_are_chosen_in_order_in_the_admin(): void
    {
        $this->actingAsAdministrator();
        $product = Product::factory()->active()->create(['sale_price' => null]);
        [$first, $second] = Product::factory()->active()->count(2)->create();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['relatedProducts' => [$second->id, $first->id], 'crossSellProducts' => [$first->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([$second->id, $first->id], $product->relatedProducts()->pluck('products.id')->all());
        $this->assertSame([$first->id], $product->crossSellProducts()->pluck('products.id')->all());
        $this->assertSame([], $product->upsellProducts()->pluck('products.id')->all());
    }

    public function test_the_product_page_shows_upsells_then_related_products(): void
    {
        $product = Product::factory()->active()->create();
        $related = Product::factory()->active()->create();
        $upsell = Product::factory()->active()->create();
        $hidden = Product::factory()->inactive()->create();
        $product->relatedProducts()->attach([$related->id => ['position' => 0], $hidden->id => ['position' => 1]]);
        $product->upsellProducts()->attach($upsell->id);

        $this->get("/shop/{$product->slug}")->assertInertia(fn (Assert $page) => $page
            ->has('related', 2)
            ->where('related.0.id', $upsell->id)
            ->where('related.1.id', $related->id)
        );
    }

    public function test_the_cart_suggests_cross_sells_of_its_products(): void
    {
        $lamp = Product::factory()->active()->create(['stock' => 5]);
        $bulb = Product::factory()->active()->create(['stock' => 5]);
        $lamp->crossSellProducts()->attach($bulb->id);

        $this->post('/cart', ['product_id' => $lamp->id, 'quantity' => 1]);

        $this->get('/cart')->assertInertia(fn (Assert $page) => $page->where('suggestions.0.id', $bulb->id));

        $this->post('/cart', ['product_id' => $bulb->id, 'quantity' => 1])->assertSessionHasNoErrors();
        $this->get('/cart')->assertInertia(fn (Assert $page) => $page->has('suggestions', 0));
    }
}
