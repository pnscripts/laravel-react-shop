<?php

namespace Tests\Feature\Cms;

use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use PnShop\Catalog\Models\Category;
use PnShop\Cms\Filament\Resources\Menus\Pages\EditMenu;
use PnShop\Cms\Filament\Resources\Menus\RelationManagers\ItemsRelationManager;
use PnShop\Cms\MenuItemType;
use PnShop\Cms\Menus;
use PnShop\Cms\Models\Menu;
use PnShop\Cms\Models\Page;
use Tests\Feature\Admin\AdminTestCase;

class MenusTest extends AdminTestCase
{
    public function test_menu_items_resolve_to_localized_links(): void
    {
        $header = Menu::query()->where('code', 'header')->sole();
        $page = Page::factory()->published()->create(['title' => 'About']);
        $page->setTranslations('bg', ['title' => 'За нас', 'slug' => 'za-nas']);
        $category = Category::factory()->create(['title' => 'Lamps']);

        $shop = $header->items()->create(['type' => MenuItemType::Shop, 'position' => 1]);
        $header->items()->create(['type' => MenuItemType::Category, 'target_id' => $category->id, 'parent_id' => $shop->id]);
        $header->items()->create(['type' => MenuItemType::Page, 'target_id' => $page->id, 'position' => 2]);
        $header->items()->create(['type' => MenuItemType::Url, 'url' => 'https://blog.example', 'label' => 'Blog', 'new_tab' => true, 'position' => 3]);
        // Gone or hidden targets are left out.
        $header->items()->create(['type' => MenuItemType::Page, 'target_id' => Page::factory()->create()->id, 'position' => 4]);
        $header->items()->create(['type' => MenuItemType::Product, 'target_id' => 999, 'position' => 5]);

        $this->assertSame([
            ['label' => 'Shop', 'url' => '/shop', 'new_tab' => false, 'children' => [['label' => 'Lamps', 'url' => '/shop?category='.$category->slug, 'new_tab' => false, 'children' => []]]],
            ['label' => 'About', 'url' => '/about', 'new_tab' => false, 'children' => []],
            ['label' => 'Blog', 'url' => 'https://blog.example', 'new_tab' => true, 'children' => []],
        ], Menus::tree('header'));

        $this->get('/bg/shop')->assertInertia(fn (Assert $inertia) => $inertia
            ->where('menus.header.0.url', '/bg/shop')
            ->where('menus.header.1.label', 'За нас')
            ->where('menus.header.1.url', '/bg/za-nas')
        );
    }

    public function test_menus_refresh_when_items_or_linked_pages_change(): void
    {
        $footer = Menu::query()->where('code', 'footer')->sole();
        $page = Page::factory()->published()->create(['title' => 'Terms']);
        $item = $footer->items()->create(['type' => MenuItemType::Page, 'target_id' => $page->id]);

        $this->assertSame('Terms', Menus::tree('footer')[0]['label']);

        $page->update(['title' => 'Terms of sale', 'slug' => 'terms-of-sale']);
        $this->assertSame('/terms-of-sale', Menus::tree('footer')[0]['url']);

        $item->update(['label' => 'Legal']);
        $this->assertSame('Legal', Menus::tree('footer')[0]['label']);

        $item->delete();
        $this->assertSame([], Menus::tree('footer'));
    }

    public function test_staff_build_menus_in_the_admin(): void
    {
        $this->actingAsAdministrator();
        $header = Menu::query()->where('code', 'header')->sole();

        Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $header, 'pageClass' => EditMenu::class])
            ->callTableAction('create', data: ['type' => 'url', 'url' => '/shop?brand=acme', 'label' => 'Acme', 'translations' => ['bg' => ['label' => 'Акме']]])
            ->assertHasNoTableActionErrors();

        $this->assertSame('Acme', Menus::tree('header')[0]['label']);
        app()->setLocale('bg');
        $this->assertSame('Акме', Menus::tree('header')[0]['label']);
        $this->assertSame('/bg/shop?brand=acme', Menus::tree('header')[0]['url']);
    }
}
