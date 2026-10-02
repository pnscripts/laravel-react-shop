# Search engines

SEO lives in `PnShop\Seo`. Its settings are in Admin → Settings → Search engines.

## What every page gets

The root template prints these tags on every full page load. They don't need JavaScript or SSR, so crawlers and link previews see them.

| Tag | Content |
|---|---|
| `<title>` | page title – store name |
| `description` | the page's meta description, or a 160-character summary of its description or excerpt |
| `canonical` | the page's address in the current language |
| `hreflang` alternates | the same page in every language (with translated slugs), plus `x-default` |
| `robots` | `index,follow`. Pages get `noindex,follow` when they are private (cart, checkout, account, orders, invoices, previews, sign-in), filtered, sorted or paged. |
| Open Graph / Twitter | title, description, type, URL, locale, image |
| JSON-LD | products: `Product` (price or price range, currency, availability, SKU, GTIN, brand, image) and `BreadcrumbList`; categories: `BreadcrumbList`; CMS pages: `WebPage`; home: `Organization` and `WebSite` |

- **Meta fields:** products, categories and CMS pages have a meta title and meta description per language (the *Search engines* section of their forms). Empty fields fall back to the title and description.
- **Language switcher:** it uses the same per-language addresses, so switching language on a product with a translated slug lands on that product.
- **Client-side navigation:** after a client-side page change only the `<title>` updates. Crawlers and link previews always load pages in full.

## Sitemaps and robots.txt

- **`/sitemap.xml`:** lists one sitemap per language for pages, categories and products. Products come in files of 5,000. Every URL lists its alternates in the other languages. The sitemaps are cached for an hour and contain only live content.
- **`/robots.txt`:**
  - is generated: it disallows the private areas in every language, filtered and sorted listings, and the admin, and points to the sitemap;
  - *Extra robots.txt rules* are appended;
  - the old static `public/robots.txt` is removed.
- **Staging copies:** turn *Let search engines index the shop* off. Every page becomes `noindex`, robots.txt disallows everything and the sitemap returns 404.

## Redirects

Old addresses keep working.

- **Automatic:**
  - when a product's or page's slug changes, in any language, a **301** from the old address to the new one is added;
  - chains are collapsed (one → three, not one → two → three);
  - an address that becomes live again stops redirecting.
- **Manual:** staff add their own redirects in Admin → Content → Redirects:
  - from a path of the shop (`/summer` or `/bg/shop/old-product`) to a path or a full URL;
  - as a 301 (permanent) or 302 (temporary) redirect;
  - hits and last use are counted.
- **When redirects apply:** only to `GET`/`HEAD` requests for addresses that would otherwise be *not found*. Live pages always win, and query strings are carried over.
- **Not covered yet:** category and brand slugs live in query strings (`/shop?category=…`), so slug changes there are not redirected yet.

## For developers

```php
use PnShop\Seo\Seo;
use PnShop\Seo\Schema;

app(Seo::class)
    ->title('Spring sale')
    ->description($text)              // summarized to 160 characters
    ->image($absoluteUrl)
    ->canonical($url)                 // also marks an address with a query string as indexable
    ->jsonLd(Schema::webPage('Spring sale', $text, $url));
```

- **Changing tags for every page:** add a stage to the `seo.meta` pipeline. It receives the `PnShop\Seo\SeoData` before it is printed.
- **Lifetime:** `Seo` is reset after each request.
- **Redirects in code:** add one with `PnShop\Seo\Redirects::add('/old', '/new')`. Watch another model's slugs with `SlugRedirects::watch(Model::class, ModelTranslation::class, fn ($slug) => '/path/'.$slug)`.
