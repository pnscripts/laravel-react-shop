<?php

namespace PnShop\Api\Http\Controllers\Store;

use Illuminate\Http\Request;
use PnShop\Api\Http\Controllers\ApiController;
use PnShop\Cms\ContentRenderer;
use PnShop\Cms\Menus;
use PnShop\Cms\Models\Menu;
use PnShop\Cms\Models\Page;

class ContentController extends ApiController
{
    /**
     * List pages
     *
     * Published CMS pages (title, slug, excerpt).
     *
     * @return array<string, mixed>
     */
    public function pages(Request $request): array
    {
        $pages = Page::query()->live()->orderBy('id')->cursorPaginate($this->perPage($request));

        return $this->paginated($pages, fn (Page $page) => [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'excerpt' => $page->excerpt,
            'is_home' => $page->is_home,
        ]);
    }

    /**
     * Show a page
     *
     * A published page with its content blocks, each `{type, data}` as the storefront renders it.
     *
     * @return array<string, mixed>
     */
    public function page(string $slug, ContentRenderer $content): array
    {
        $page = Page::query()->live()->whereTranslated('slug', $slug)->firstOrFail();

        return ['data' => [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'excerpt' => $page->excerpt,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'is_home' => $page->is_home,
            'updated_at' => $page->updated_at?->toIso8601String(),
            'blocks' => $content->render($page),
        ]];
    }

    /**
     * Show a menu
     *
     * A menu's items by its code (e.g. `header`, `footer`), nested, with resolved URLs.
     *
     * @return array<string, mixed>
     */
    public function menu(string $code): array
    {
        abort_unless(preg_match('/^[a-z0-9_-]{1,64}$/', $code) === 1 && Menu::query()->where('code', $code)->exists(), 404);

        return ['data' => ['code' => $code, 'items' => Menus::tree($code)]];
    }
}
