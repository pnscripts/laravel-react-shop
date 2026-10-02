<?php

namespace PnShop\Seo;

use Brick\Money\Money;
use PnShop\Settings\Settings;

/**
 * schema.org objects for JSON-LD.
 */
final class Schema
{
    /**
     * @param  list<string>  $images
     * @return array<string, mixed>
     */
    public static function product(string $name, ?string $description, string $url, array $images, ?string $sku, ?string $gtin, ?string $brand, Money $price, bool $inStock, ?Money $highPrice = null): array
    {
        $offer = $highPrice !== null && $highPrice->isGreaterThan($price)
            ? ['@type' => 'AggregateOffer', 'lowPrice' => (string) $price->getAmount(), 'highPrice' => (string) $highPrice->getAmount()]
            : ['@type' => 'Offer', 'price' => (string) $price->getAmount()];

        return array_filter([
            '@type' => 'Product',
            'name' => $name,
            'description' => Seo::summary($description, 5000),
            'url' => $url,
            'image' => $images ?: null,
            'sku' => $sku,
            'gtin' => $gtin,
            'brand' => $brand !== null ? ['@type' => 'Brand', 'name' => $brand] : null,
            'offers' => [
                ...$offer,
                'priceCurrency' => $price->getCurrency()->getCurrencyCode(),
                'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => $url,
            ],
        ], fn (mixed $value) => $value !== null);
    }

    /**
     * @param  list<array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $item, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ], $items, array_keys($items)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function organization(string $url): array
    {
        $settings = app(Settings::class);

        return array_filter([
            '@type' => 'Organization',
            'name' => (string) $settings->get('store.name'),
            'url' => $url,
            'email' => $settings->get('store.email') ?: null,
            'telephone' => $settings->get('store.phone') ?: null,
        ], fn (mixed $value) => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public static function website(string $url): array
    {
        return ['@type' => 'WebSite', 'name' => (string) app(Settings::class)->get('store.name'), 'url' => $url];
    }

    /**
     * @return array<string, mixed>
     */
    public static function webPage(string $name, ?string $description, string $url): array
    {
        return array_filter(['@type' => 'WebPage', 'name' => $name, 'description' => $description, 'url' => $url], fn (mixed $value) => $value !== null);
    }
}
