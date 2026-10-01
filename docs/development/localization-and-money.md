# Localization and money

## Languages and URLs

Languages are managed in **Admin → Store → Languages**. The default language is the store's *source language*:
- catalog and content columns hold its text;
- every other language is stored in translation tables.

The default language cannot be deactivated or deleted.

| URL | Language |
|---|---|
| `/shop` | default language (no prefix) |
| `/bg/shop` | Bulgarian |
| `/en/shop` | 301 redirect to `/shop` when English is the default |

`PnShop\Localization\Http\Middleware\LocalizeRequest` (global middleware) handles the prefix:
- It sets the locale and applies the store timezone setting.
- It makes the prefix the request's **base URL**, exactly as if the store were installed in a `/bg` subdirectory. Routes stay unprefixed, so `php artisan route:cache` keeps working.
- Everything Laravel derives from the request keeps the language without extra code: `route()`, `url()`, pagination links, redirects, `back()` and Ziggy.
- Assets keep the real root.
- The admin (`/admin`), Livewire, the API and `/storage` are never localized.

The storefront receives `localization` (current locale, store currency and one switch link per language) as a shared prop. `<LanguageSwitcher />` renders the links as plain links (full page loads).

## Translating content: `Translatable`

```php
use PnShop\Localization\Concerns\Translatable;
use PnShop\Localization\Contracts\TranslatableModel;

class Product extends Model implements TranslatableModel
{
    use Translatable;

    protected array $translatable = ['title', 'slug', 'description'];
}
```

- **Storage:** a `product_translations` table (`ProductTranslation` model in the same namespace) with `product_id`, `locale` and one nullable column per translatable attribute, unique on `(product_id, locale)` and `(locale, slug)`.
- **Reading:** `$product->title` returns the current locale's value, falling back to the source language. Explicit reads use `$product->translation('title', 'bg')`. Serialized models (`toArray`, Inertia props) carry translated values and never the raw translation rows.
- **Writing:** `$product->setTranslations('bg', ['title' => '…'])`. A translated slug is generated from the title when empty and kept unique per language. Writing the default language updates the model's own columns.
- **Querying:** `Product::whereTranslated('slug', $value)` matches the current language or the source language. Route model binding on a translatable key (`{product:slug}`) uses it automatically.
- **Performance:** outside the default language, a global scope eager-loads only the current locale's rows, which costs one query per model type. The default language costs nothing extra. `tests/Feature/Core/QueryCountTest.php` keeps page query counts constant as the catalog grows.
- **Admin:** add `TranslationsSection::make([...])` to a Filament form (one tab per language) and `use SavesTranslations;` on its Create/Edit pages.

## Interface text

- **PHP:** user-facing messages use `__('English text', ['name' => …])`.
- **Translations file:** `lang/<locale>.json` holds the translations, keyed by the English text. Validation, auth, password and pagination messages live in `lang/<locale>/*.php`.
- **React:** `const t = useTranslations(); t('Add to cart'); t(':count in stock', { count })`. The current language's JSON is shared once per full page load (Inertia `once` prop), and English sends nothing.
- **Placeholders:** they use Laravel's `:name` style, and a test checks that every translation keeps the same placeholders as its key.

Adding a language:
1. Add it in Admin → Store → Languages.
2. Add `lang/<code>.json` and, optionally, `lang/<code>/*.php`.
3. Translate content in each product's *Translations* section.

## Money

- **Storage:** amounts are integers in **minor units** of the currency (`12.50 USD` → `1250`). Orders and order lines store the `currency` they were placed in.
- **Cast:** `PnShop\Money\MoneyCast` exposes `Brick\Money\Money`. Use `MoneyCast::class` for the store's default currency, or `MoneyCast::class.':currency'` to read the currency from a column.
  - It accepts `Money` or decimal input (`'12.50'`, rounded half-up to the currency precision), and refuses money in another currency.
  - Serialized values are decimal strings, which is what admin forms work with.
- **Arithmetic:** use `Money` methods only (`plus`, `multipliedBy`, …), never floats. `OrderItem::lineTotal()` and `Order::itemsTotal()` are examples.
- **Storefront:** props use `MoneyPresenter::present($money)` → `{ amount, minor, currency, formatted }`. `formatted` is produced on the server for the visitor's language (`$1,234.50`, `1234,50 щ.д.`), and React components display `price.formatted`.
- **Currencies:** managed in Admin → Store → Currencies. The default currency is the one prices are entered in. Per-currency price lists arrive with the catalog redesign.
