<?php

namespace PnShop\Cms\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use PnShop\Cms\Blocks\BlockRegistry;
use PnShop\Cms\Models\ContentBlock;

/**
 * Ordered content blocks per area and language.
 *
 * @mixin Model
 */
trait HasContentBlocks
{
    /**
     * @return MorphMany<ContentBlock, $this>
     */
    public function contentBlocks(): MorphMany
    {
        return $this->morphMany(ContentBlock::class, 'owner')->orderBy('position');
    }

    /**
     * The blocks of an area in one language, as stored.
     *
     * @return list<array{type: string, data: array<string, mixed>}>
     */
    public function blocksFor(string $area, string $locale): array
    {
        return array_values($this->contentBlocks()->where('area', $area)->where('locale', $locale)->get()
            ->map(fn (ContentBlock $block) => ['type' => $block->type, 'data' => $block->data])
            ->all());
    }

    /**
     * Replace the blocks of an area in one language. Each block type prepares its data
     * for storage (e.g. registers uploaded images in the media library).
     *
     * @param  iterable<array{type: string, data?: array<string, mixed>|null}>  $blocks
     */
    public function syncBlocks(string $area, string $locale, iterable $blocks): void
    {
        $registry = app(BlockRegistry::class);

        $this->contentBlocks()->where('area', $area)->where('locale', $locale)->delete();

        $position = 0;

        foreach ($blocks as $block) {
            if (! $registry->has($block['type'])) {
                continue;
            }

            $this->contentBlocks()->create([
                'area' => $area,
                'locale' => $locale,
                'position' => $position++,
                'type' => $block['type'],
                'data' => $registry->get($block['type'])->store($block['data'] ?? []),
            ]);
        }
    }
}
