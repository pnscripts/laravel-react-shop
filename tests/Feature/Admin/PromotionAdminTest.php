<?php

namespace Tests\Feature\Admin;

use Filament\Forms\Components\Builder;
use Livewire\Livewire;
use PnShop\Promotion\Filament\Resources\Promotions\Pages\CreatePromotion;
use PnShop\Promotion\Filament\Resources\Promotions\Pages\EditPromotion;
use PnShop\Promotion\Filament\Resources\Promotions\PromotionResource;
use PnShop\Promotion\Filament\Resources\Promotions\RelationManagers\CouponsRelationManager;
use PnShop\Promotion\Models\Coupon;
use PnShop\Promotion\Models\Promotion;

class PromotionAdminTest extends AdminTestCase
{
    public function test_marketing_staff_build_a_promotion_from_conditions_and_actions(): void
    {
        $this->actingAsStaff(['marketing.promotions.manage']);

        $this->get(PromotionResource::getUrl('index'))->assertOk();

        $undo = Builder::fake();

        Livewire::test(CreatePromotion::class)
            ->fillForm([
                'name' => 'Spring',
                'requires_coupon' => true,
                'conditions' => [['type' => 'subtotal', 'data' => ['min' => '50']]],
                'actions' => [['type' => 'percent_off', 'data' => ['percent' => '10']], ['type' => 'free_shipping', 'data' => []]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $undo();

        $promotion = Promotion::query()->sole();
        $this->assertTrue($promotion->requires_coupon);
        $this->assertSame(['subtotal'], array_column(array_values($promotion->conditions ?? []), 'type'));
        $this->assertSame(['percent_off', 'free_shipping'], array_column(array_values($promotion->actions ?? []), 'type'));

        $this->get(PromotionResource::getUrl('edit', ['record' => $promotion]))->assertOk();
    }

    public function test_coupons_are_added_and_generated(): void
    {
        $this->actingAsStaff(['marketing.promotions.manage']);
        $promotion = Promotion::factory()->create(['requires_coupon' => true]);

        $manager = fn () => Livewire::test(CouponsRelationManager::class, ['ownerRecord' => $promotion, 'pageClass' => EditPromotion::class]);

        $manager()->callTableAction('create', data: ['code' => 'spring-24', 'is_active' => true])->assertHasNoTableActionErrors();
        // Codes are unique whatever their case.
        $manager()->callTableAction('create', data: ['code' => 'SPRING-24', 'is_active' => true])->assertHasTableActionErrors(['code']);
        $manager()->callTableAction('generate', data: ['count' => 5, 'prefix' => 'nl-', 'usage_limit' => 1])->assertHasNoTableActionErrors();

        $this->assertSame(6, Coupon::query()->count());
        $this->assertSame(5, Coupon::query()->where('code', 'like', 'NL-%')->where('usage_limit', 1)->count());
    }

    public function test_others_cannot_open_promotions(): void
    {
        $this->actingAsStaff(['catalog.products.view']);

        $this->get(PromotionResource::getUrl('index'))->assertForbidden();
    }
}
