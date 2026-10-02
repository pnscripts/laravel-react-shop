<?php

namespace PnShop\Seo;

/**
 * What the current page tells search engines and link previews.
 */
final class SeoData
{
    public ?string $title = null;

    public ?string $description = null;

    public ?string $canonical = null;

    /** @var array<string, string> locale => URL of this page in that language */
    public array $alternates = [];

    public bool $index = true;

    public ?string $image = null;

    public string $type = 'website';

    /** @var list<array<string, mixed>> */
    public array $jsonLd = [];
}
