<?php

namespace PnShop\Cms;

use Illuminate\Support\Facades\DB;
use PnShop\Acl\Models\AdminUser;
use PnShop\Cms\Models\ContentBlock;
use PnShop\Cms\Models\Page;
use PnShop\Cms\Models\PageRevision;
use PnShop\Settings\Settings;

/**
 * Keeps a full copy of a page on every save and restores one as a new save. The number
 * of revisions kept per page is the "cms.revisions_keep" setting.
 */
class PageRevisions
{
    public function __construct(private Settings $settings) {}

    public function record(Page $page, ?AdminUser $admin = null): PageRevision
    {
        $revision = $page->revisions()->create(['snapshot' => $this->snapshot($page), 'admin_user_id' => $admin?->id]);

        $keep = max(1, (int) $this->settings->get('cms.revisions_keep'));
        $page->revisions()->skip($keep)->take(PHP_INT_MAX)->pluck('id')
            ->whenNotEmpty(fn ($old) => PageRevision::query()->whereKey($old)->delete());

        return $revision;
    }

    public function restore(PageRevision $revision, ?AdminUser $admin = null): Page
    {
        $page = $revision->page()->withTrashed()->firstOrFail();
        $snapshot = $revision->snapshot;

        DB::transaction(function () use ($page, $snapshot) {
            $page->fill($snapshot['attributes'])->save();

            foreach ($snapshot['translations'] as $locale => $values) {
                $page->setTranslations($locale, $values);
            }

            $page->contentBlocks()->delete();

            foreach ($snapshot['blocks'] as $area => $locales) {
                foreach ($locales as $locale => $blocks) {
                    $page->syncBlocks($area, $locale, $blocks);
                }
            }
        });

        $this->record($page->refresh(), $admin);

        return $page;
    }

    /**
     * @return array{attributes: array<string, mixed>, translations: array<string, array<string, mixed>>, blocks: array<string, array<string, list<array{type: string, data: array<string, mixed>}>>>}
     */
    public function snapshot(Page $page): array
    {
        $attributes = $page->only(['title', 'slug', 'excerpt', 'meta_title', 'meta_description', 'template']);
        $attributes['status'] = $page->status->value;
        $attributes['published_at'] = $page->published_at?->toIso8601String();
        $attributes['unpublished_at'] = $page->unpublished_at?->toIso8601String();

        $blocks = [];

        foreach ($page->contentBlocks()->get() as $block) {
            /** @var ContentBlock $block */
            $blocks[$block->area][$block->locale][] = ['type' => $block->type, 'data' => $block->data];
        }

        return [
            'attributes' => $attributes,
            'translations' => $page->translations()->get()->mapWithKeys(fn ($translation) => [
                $translation->getAttribute('locale') => $translation->only(['title', 'slug', 'excerpt', 'meta_title', 'meta_description']),
            ])->all(),
            'blocks' => $blocks,
        ];
    }
}
