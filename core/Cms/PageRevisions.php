<?php

namespace PnShop\Cms;

use Illuminate\Support\Facades\DB;
use PnShop\Acl\Models\AdminUser;
use PnShop\Cms\Blocks\BlockRegistry;
use PnShop\Cms\Blocks\BlockType;
use PnShop\Cms\Exceptions\LockedBlocksException;
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

    /**
     * @throws LockedBlocksException when the staff member may not change a block type (custom
     *                               HTML) that the restore would bring back, change or remove
     */
    public function restore(PageRevision $revision, ?AdminUser $admin = null): Page
    {
        $page = $revision->page()->withTrashed()->firstOrFail();
        $snapshot = $revision->snapshot;

        $this->assertMayRestoreLockedBlocks($page, $snapshot['blocks'], $admin);

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
     * The same rule as the content editor: without a block type's permission, its blocks
     * must come out of the restore exactly as they are now.
     *
     * @param  array<string, array<string, list<array{type: string, data: array<string, mixed>}>>>  $restored
     *
     * @throws LockedBlocksException
     */
    private function assertMayRestoreLockedBlocks(Page $page, array $restored, ?AdminUser $admin): void
    {
        $locked = array_keys(array_filter(
            app(BlockRegistry::class)->all(),
            fn (BlockType $type) => $type->permission() !== null && ! ($admin?->can($type->permission()) ?? false),
        ));

        if ($locked === []) {
            return;
        }

        $current = $this->snapshot($page)['blocks'];
        $data = function (array $areas) use ($locked): array {
            $found = [];

            foreach ($areas as $area => $locales) {
                foreach ($locales as $locale => $blocks) {
                    foreach ($blocks as $block) {
                        if (in_array($block['type'] ?? null, $locked, true)) {
                            $found[] = $area.'|'.$locale.'|'.json_encode($block['data'] ?? []);
                        }
                    }
                }
            }

            sort($found);

            return $found;
        };

        if ($data($restored) !== $data($current)) {
            throw new LockedBlocksException(__('This version has different custom HTML blocks, which you may not change.'));
        }
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
