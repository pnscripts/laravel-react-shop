<?php

namespace PnShop\Localization;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Collection;
use PnShop\Localization\Models\Currency;
use PnShop\Localization\Models\Language;
use RuntimeException;

/**
 * Active languages and currencies, cached as plain arrays.
 *
 * The default language is the store's source language: catalog and content
 * columns hold its text, and other languages are stored in translation tables.
 */
final class Localization
{
    private const CACHE_KEY = 'pnshop.localization';

    /** Path prefixes that are never localized. */
    public const UNLOCALIZED_PATHS = ['admin', 'livewire', 'api', 'storage', 'build', 'up', '_debugbar', 'telescope', 'filament'];

    /** @var array{languages: list<array<string, mixed>>, currency: array<string, mixed>|null}|null */
    private ?array $data = null;

    /** @var (\Closure(string): ?string)|null */
    private ?\Closure $alternateResolver = null;

    public function __construct(private Cache $cache) {}

    /**
     * Active languages, default first, then by sort order.
     *
     * @return Collection<int, Language>
     */
    public function languages(): Collection
    {
        return collect($this->data()['languages'])
            ->map(fn (array $attributes) => (new Language)->forceFill($attributes));
    }

    public function defaultLocale(): string
    {
        return $this->languages()->first()->code ?? config('app.locale');
    }

    public function isSupported(string $locale): bool
    {
        return $this->languages()->contains('code', $locale);
    }

    /**
     * The URL prefix for a locale: none for the default language, "/bg" for others.
     */
    public function prefix(string $locale): string
    {
        return $locale === $this->defaultLocale() ? '' : '/'.$locale;
    }

    /**
     * The same page in another language.
     *
     * @param  string  $origin  scheme, host and install base without any language prefix, e.g. https://shop.test
     */
    public function switchUrl(string $locale, string $origin, string $unprefixedPath, ?string $query = null): string
    {
        $path = '/'.ltrim($unprefixedPath, '/');
        $prefix = $this->prefix($locale);

        if ($prefix !== '') {
            $path = $path === '/' ? $prefix : $prefix.$path;
        }

        return rtrim($origin, '/').$path.($query ? '?'.$query : '');
    }

    /**
     * Let another module (SEO) supply the current page's address in a language, for pages
     * whose path differs per language (translated slugs).
     *
     * @param  \Closure(string): ?string  $resolver  locale => absolute URL or null
     */
    public function resolveAlternatesUsing(\Closure $resolver): void
    {
        $this->alternateResolver = $resolver;
    }

    public function alternateFor(string $locale): ?string
    {
        return $this->alternateResolver === null ? null : ($this->alternateResolver)($locale);
    }

    public function defaultCurrency(): Currency
    {
        $attributes = $this->data()['currency'] ?? throw new RuntimeException('No default currency is configured.');

        return (new Currency)->forceFill($attributes);
    }

    public function flush(): void
    {
        $this->data = null;
        $this->cache->forget(self::CACHE_KEY);
    }

    /**
     * @return array{languages: list<array<string, mixed>>, currency: array<string, mixed>|null}
     */
    private function data(): array
    {
        return $this->data ??= $this->cache->rememberForever(self::CACHE_KEY, fn () => [
            'languages' => array_values(Language::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('sort_order')
                ->get(['id', 'code', 'name', 'native_name', 'is_default', 'is_active', 'sort_order'])
                ->map(fn (Language $language) => $language->getAttributes())
                ->all()),
            'currency' => Currency::query()->where('is_default', true)->first()?->getAttributes(),
        ]);
    }
}
