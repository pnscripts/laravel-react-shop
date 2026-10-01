<?php

namespace Tests\Feature\Admin;

use Livewire\Livewire;
use PnShop\Cms\Blocks\BlockRegistry;
use PnShop\Cms\Filament\Resources\Pages\Pages\CreatePage;
use PnShop\Cms\Filament\Resources\Pages\Pages\EditPage;
use PnShop\Cms\Filament\Resources\Pages\RelationManagers\RevisionsRelationManager;
use PnShop\Cms\Models\Page;
use PnShop\Cms\PageStatus;

class PageResourceTest extends AdminTestCase
{
    public function test_a_page_is_composed_from_blocks_in_the_admin(): void
    {
        $this->actingAsAdministrator();

        Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => 'Our story',
                'status' => PageStatus::Published->value,
                'content_blocks' => ['body' => ['en' => [
                    'a' => ['type' => 'call_to_action', 'data' => ['heading' => 'Visit us', 'button_label' => 'Map', 'button_url' => '/shop']],
                    'b' => ['type' => 'rich_text', 'data' => ['content' => '<p>Since 2020.</p>']],
                ]]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $page = Page::query()->sole();
        $this->assertSame('our-story', $page->slug);
        $this->assertSame(['call_to_action', 'rich_text'], array_column($page->blocksFor('body', 'en'), 'type'));
        $this->assertSame(1, $page->revisions()->count());

        $this->get('/our-story')->assertOk()->assertInertia(fn ($inertia) => $inertia->where('blocks.1.props.html', '<p>Since 2020.</p>'));
    }

    public function test_slugs_used_by_the_shop_are_refused(): void
    {
        $this->actingAsAdministrator();

        Livewire::test(CreatePage::class)
            ->fillForm(['title' => 'Checkout help', 'slug' => 'checkout', 'status' => 'draft'])
            ->call('create')
            ->assertHasFormErrors(['slug']);

        Livewire::test(CreatePage::class)
            ->fillForm(['title' => 'Bulgarian', 'slug' => 'bg', 'status' => 'draft'])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }

    public function test_only_permitted_staff_add_custom_html(): void
    {
        $page = Page::factory()->create();
        $page->syncBlocks('body', 'en', [['type' => 'html', 'data' => ['html' => '<div id="widget"></div>']]]);

        $this->actingAsStaff(['content.pages.manage']);

        // Editing other parts of the page keeps the existing HTML block.
        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['title' => 'Renamed'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('<div id="widget"></div>', $page->fresh()->blocksFor('body', 'en')[0]['data']['html']);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['content_blocks' => ['body' => ['en' => ['x' => ['type' => 'html', 'data' => ['html' => '<script>steal()</script>']]]]]])
            ->call('save')
            ->assertHasFormErrors(['content_blocks.body.en']);

        $this->assertSame('<div id="widget"></div>', $page->fresh()->blocksFor('body', 'en')[0]['data']['html']);
    }

    public function test_a_revision_can_be_restored(): void
    {
        $this->actingAsAdministrator();
        $page = Page::factory()->create(['title' => 'First title']);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['content_blocks' => ['body' => ['en' => ['a' => ['type' => 'rich_text', 'data' => ['content' => '<p>Version one</p>']]]]]])
            ->call('save');
        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['title' => 'Second title', 'content_blocks' => ['body' => ['en' => []]]])
            ->call('save');

        $first = $page->revisions()->get()->last();

        Livewire::test(RevisionsRelationManager::class, ['ownerRecord' => $page->fresh(), 'pageClass' => EditPage::class])
            ->callTableAction('restore', $first);

        $page->refresh();
        $this->assertSame('First title', $page->title);
        $this->assertStringContainsString('Version one', app(BlockRegistry::class)->render($page->blocksFor('body', 'en'))[0]['props']['html']);
        $this->assertSame(3, $page->revisions()->count());
    }
}
