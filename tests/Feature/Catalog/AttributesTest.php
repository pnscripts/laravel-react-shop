<?php

namespace Tests\Feature\Catalog;

use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use PnShop\Catalog\Filament\Resources\Attributes\Pages\CreateAttribute;
use PnShop\Catalog\Filament\Resources\Products\Pages\EditProduct;
use PnShop\Catalog\Models\Brand;
use PnShop\Catalog\Models\Product;
use PnShop\Catalog\Models\ProductAttribute;
use PnShop\Catalog\Models\ProductAttributeValue;
use Tests\Feature\Admin\AdminTestCase;

class AttributesTest extends AdminTestCase
{
    public function test_attributes_with_translated_values_are_managed_in_the_admin(): void
    {
        $this->actingAsAdministrator();

        Livewire::test(CreateAttribute::class)
            ->fillForm([
                'label' => 'Material',
                'key' => 'material',
                'type' => 'select',
                'is_filterable' => true,
                'translations' => ['bg' => ['label' => 'Материал']],
                'values' => [
                    ['value' => 'Wood', 'translations_input' => ['bg' => ['value' => 'Дърво']]],
                    ['value' => 'Metal', 'translations_input' => ['bg' => ['value' => '']]],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $attribute = ProductAttribute::query()->where('key', 'material')->sole();
        $this->assertTrue($attribute->is_filterable);
        $this->assertSame('Материал', $attribute->translation('label', 'bg'));
        $this->assertSame(['Wood', 'Metal'], $attribute->values->pluck('value')->all());
        $this->assertSame('Дърво', $attribute->values[0]->translation('value', 'bg'));
        $this->assertNull($attribute->values[1]->translation('value', 'bg'));
    }

    public function test_attribute_values_are_chosen_on_the_product_form(): void
    {
        $this->actingAsAdministrator();
        [$material, $wood] = $this->material();
        $product = Product::factory()->active()->create(['sale_price' => null]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['selectedAttributeValues' => [$wood->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([$wood->id], $product->selectedAttributeValues()->pluck('product_attribute_values.id')->all());
    }

    public function test_the_shop_filters_by_attributes_with_or_inside_and_and_across(): void
    {
        [$material, $wood, $metal] = $this->material();
        [$finish, $matte] = $this->attribute('finish', 'Finish', ['Matte', 'Glossy']);

        $woodMatte = Product::factory()->active()->create(['title' => 'Wood matte']);
        $woodMatte->selectedAttributeValues()->attach([$wood->id, $matte->id]);
        $metalOnly = Product::factory()->active()->create(['title' => 'Metal']);
        $metalOnly->selectedAttributeValues()->attach($metal->id);
        Product::factory()->active()->create(['title' => 'Plain']);

        $this->get("/shop?filter[{$material->id}][]={$wood->id}&filter[{$material->id}][]={$metal->id}")
            ->assertInertia(fn (Assert $page) => $page->has('products.data', 2));

        $this->get("/shop?filter[{$material->id}][]={$metal->id}&filter[{$finish->id}][]={$matte->id}")
            ->assertInertia(fn (Assert $page) => $page->has('products.data', 0));

        $this->get("/shop?filter[{$material->id}][]={$wood->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.id', $woodMatte->id)
                ->where("filters.attributes.{$material->id}", [$wood->id])
            );
    }

    public function test_only_values_present_in_the_current_selection_are_offered(): void
    {
        [$material, $wood, $metal] = $this->material();
        $brand = Brand::create(['name' => 'Oakline']);
        Product::factory()->active()->create(['brand_id' => $brand->id])->selectedAttributeValues()->attach($wood->id);
        Product::factory()->active()->create()->selectedAttributeValues()->attach($metal->id);

        $this->get('/shop')->assertInertia(fn (Assert $page) => $page
            ->where('facets.0.label', 'Material')
            ->has('facets.0.values', 2)
        );

        $this->get('/shop?brand=oakline')->assertInertia(fn (Assert $page) => $page
            ->has('facets.0.values', 1)
            ->where('facets.0.values.0.value', 'Wood')
        );
    }

    public function test_the_attribute_screen_needs_its_permission(): void
    {
        $this->actingAsStaff(['catalog.products.view']);

        $this->get('/admin/attributes')->assertForbidden();
    }

    /**
     * @return array{ProductAttribute, ProductAttributeValue, ProductAttributeValue}
     */
    private function material(): array
    {
        return $this->attribute('material', 'Material', ['Wood', 'Metal']);
    }

    /**
     * @param  list<string>  $values
     * @return array{ProductAttribute, ProductAttributeValue, ProductAttributeValue}
     */
    private function attribute(string $key, string $label, array $values): array
    {
        $attribute = ProductAttribute::query()->create(['key' => $key, 'label' => $label, 'type' => 'select', 'is_filterable' => true]);

        foreach ($values as $position => $value) {
            $attribute->values()->create(['value' => $value, 'position' => $position]);
        }

        $attribute->load('values');

        return [$attribute, $attribute->values[0], $attribute->values[1]];
    }
}
