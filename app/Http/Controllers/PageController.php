<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use PnShop\Cms\ContentRenderer;
use PnShop\Cms\Models\Page;

class PageController extends Controller
{
    public function __construct(private ContentRenderer $content) {}

    /**
     * CMS pages live at /{slug} (in the current language). Registered as the fallback
     * route, so the shop's own routes always win.
     */
    public function show(Request $request): Response
    {
        $slug = trim($request->path(), '/');

        abort_unless(preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1, 404);

        $page = Page::query()->live()->whereTranslated('slug', $slug)->first();

        abort_if($page === null, 404);

        return $this->render($page);
    }

    /**
     * Staff preview drafts and scheduled pages through a signed link from the admin.
     */
    public function preview(Page $page): Response
    {
        return $this->render($page, preview: ! $page->isLive());
    }

    private function render(Page $page, bool $preview = false): Response
    {
        return Inertia::render('cms/page', [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'excerpt' => $page->excerpt,
                'preview' => $preview,
            ],
            'blocks' => $this->content->render($page),
        ]);
    }
}
