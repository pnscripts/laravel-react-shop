<?php

namespace PnShop\Cms;

use Illuminate\Database\Eloquent\Model;
use PnShop\Cms\Blocks\BlockRegistry;
use PnShop\Localization\Localization;

/**
 * Storefront props for a content area in the current language, falling back to the
 * default language when the current one has no blocks.
 */
class ContentRenderer
{
    public function __construct(
        private BlockRegistry $blocks,
        private Localization $localization,
    ) {}

    /**
     * @return list<array{type: string, props: array<string, mixed>}>
     */
    public function render(Model $owner, string $area = 'body'): array
    {
        if (! method_exists($owner, 'blocksFor')) {
            return [];
        }

        $blocks = $owner->blocksFor($area, app()->getLocale());

        if ($blocks === [] && app()->getLocale() !== $this->localization->defaultLocale()) {
            $blocks = $owner->blocksFor($area, $this->localization->defaultLocale());
        }

        return $this->blocks->render($blocks);
    }
}
