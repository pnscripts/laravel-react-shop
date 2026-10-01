<?php

namespace PnShop\Media;

use PnShop\Media\Models\Media;

/**
 * The image shape used by the storefront.
 */
final class MediaPresenter
{
    /**
     * @return array{id: int, url: string, thumb: string, srcset: string, alt: string, width: int|null, height: int|null}
     */
    public static function present(Media $media, ?string $fallbackAlt = null): array
    {
        return [
            'id' => $media->id,
            'url' => $media->url('large'),
            'thumb' => $media->url('thumb'),
            'srcset' => $media->srcset(),
            'alt' => $media->alt ?? $fallbackAlt ?? '',
            'width' => $media->width,
            'height' => $media->height,
        ];
    }
}
