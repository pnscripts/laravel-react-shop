# Core modules and the extension kernel

PN Shop's platform code is the Composer package `pnscripts/pn-shop-core` (namespace `PnShop\`). In this repository it lives in `packages/pn-shop-core`, which Composer links into `vendor/` through a path repository; shops install it from Packagist. `app/` is the merchant's application space: the customer `User` model (it extends `PnShop\Customer\Models\User`), their own providers, routes and code.

## Layout

```
packages/pn-shop-core/
├── composer.json            the package; registers PnShopServiceProvider through package discovery
├── config/pnshop.php        defaults, merged under the shop's config/pnshop.php
├── database/                base migrations (customers, cache, queue, the original catalog and orders) and seeders
├── lang/                    interface translations (bg.json)
├── resources/               the storefront: js/ (React pages, components), css/, views/ (root template, invoices)
├── routes/                  the storefront's routes (web, auth, account settings)
├── theme/                   the built-in theme's manifest; dist/ holds the prebuilt storefront in releases
├── storefront-sdk/          @pnshop/storefront-sdk for plugin authors
└── src/                     the modules:

src/
├── Foundation/              PnShop (version, module list), PnShopServiceProvider, ModuleServiceProvider
│   └── Extension/           Permission, PermissionRegistry, PipelineRegistry
├── Settings/                typed, cached settings + admin page
├── Localization/            languages, currencies, countries, localized URLs, Translatable
├── Money/                   MoneyCast, MoneyPresenter (minor units, Brick Money)
├── Acl/                     staff accounts (admin_users), roles, permissions, commands
├── System/                  activity log
├── Catalog/                 products, variants, options, categories, brands, attributes, relations + admin
├── Inventory/               stock locations, levels, movement ledger, InventoryService
├── Media/                   media library, uploads, WebP conversions, HasMedia
├── Customer/                customer groups, address books, PostalAddress + admin
├── Cart/                    database carts, guest merge, CartCalculator (cart.totals pipeline)
├── Security/                bot-trap middleware (honeypot, time trap), CaptchaVerifier
├── Sales/                   orders, OrderWorkflow (states, history), checkout, admin resources, dashboard widgets
├── Payment/                 gateways (PaymentGatewayManager), payment methods, payments, contract test kit
├── Shipping/                zones, methods, carriers (ShippingCarrierManager), shipments, cart.totals stage, contract test kit
├── Tax/                     tax classes, zones, rates, TaxProvider (rate tables by default), cart.totals stage
├── Promotion/               promotions (PromotionRegistry: conditions/actions), coupons, redemptions, cart.totals stages
├── Returns/                 return requests (RMA): ReturnService, statuses, admin workflow, emails
├── Cms/                     pages, revisions, content blocks (BlockRegistry, HasContentBlocks, ContentEditor), menus
├── Seo/                     meta tags, hreflang, JSON-LD (Schema), sitemaps, robots.txt, seo.meta pipeline, redirects
├── Extension/               plugin manifest, discovery, lifecycle (ExtensionManager), boot loader, integrity, zip uploads
├── Theme/                   theme manifests, active theme and fallback, publishing, settings as CSS variables
├── Api/                     Store API and Admin API (Sanctum tokens, problem+json, idempotency, rate limits, OpenAPI)
├── Installer/               pnshop:install, web installer (/install), pnshop:update, pnshop:migrate-to-package, backups
├── Storefront/              storefront controllers, middleware, form requests, rate limits, route loading
└── Admin/                   the Filament panel (/admin)
```

Modules are listed, in boot order, in `PnShop::MODULES`: they belong to the package, so a module added by an update loads without configuration changes. A shop adds its own module providers with `extra_modules` in `config/pnshop.php`. `PnShopServiceProvider` (found by Laravel's package discovery) registers the kernel singletons and then every module.

The shop's own `routes/web.php` loads after the storefront's routes and before the CMS page fallback, so merchants can add routes there.

A module provider extends `PnShop\Foundation\ModuleServiceProvider`, which:

- loads `src/<Module>/database/migrations` automatically;
- registers the module's `permissions()` with the permission registry;
- calls `bootModule()` for anything else (policies, settings schemas, listeners).

Admin screens are discovered from `src/<Module>/Filament/{Resources,Pages,Widgets}`. A module needs no panel changes to add screens.

## Extension kernel

These are the stable extension points that core modules and plugins use (see [plugins](../extensions/plugins.md)).

### Permissions

```php
use PnShop\Foundation\Extension\Permission;

protected function permissions(): array
{
    return [
        new Permission('catalog.brands.manage', 'Manage brands', 'Catalog'),
    ];
}
```

- Keys are dotted lowercase: `<area>.<resource>.<action>`.
- `PermissionSynchronizer` writes new keys to the database after every `php artisan migrate` (and on `pnshop:permissions:sync`).
- A newly created permission is granted once to each built-in role whose pattern matches it (see `PnShop\Acl\DefaultRoles`). Later edits in the admin are never overwritten.
- Permissions that are no longer registered are kept, so disabling and re-enabling an extension preserves role assignments.
- Administrators pass every permission check through `Gate::before`, but **policy abilities** (`delete`, `update`, …) still run their own rules.

### Pipelines

```php
use PnShop\Foundation\Extension\PipelineRegistry;

app(PipelineRegistry::class)->stage('cart.totals', ApplyLoyaltyDiscount::class, priority: 200);
$result = app(PipelineRegistry::class)->run('cart.totals', $cart);
```

Stages work like middleware: `handle($payload, Closure $next)`. Lower priorities run first. Pipelines replace "override a core class" patterns. `cart.totals` is live (see [customers, cart and checkout](../ecommerce/customers-cart-and-checkout.md#totals-pipeline)); price resolution follows in Phase 6.

### Settings

```php
use PnShop\Settings\{SettingDefinition, SettingsRegistry, SettingsSchema, SettingType};

app(SettingsRegistry::class)->register(new SettingsSchema(
    'plugin.acme.seo',               // namespace: store, seo, theme.<id>, plugin.<vendor>.<name>
    'Advanced SEO',                  // tab label in Admin → System → Settings
    new SettingDefinition('enabled', SettingType::Boolean, 'Enabled', default: true),
    new SettingDefinition('title_suffix', SettingType::String, 'Title suffix', rules: ['max:60']),
));

app(\PnShop\Settings\Settings::class)->get('plugin.acme.seo.enabled');      // typed, cached
app(\PnShop\Settings\Settings::class)->set('plugin.acme.seo', ['enabled' => false]); // validated
```

- Every schema gets its own tab on the settings page automatically.
- Values are stored per `(namespace, key)` in the `settings` table, so extensions cannot write into each other's or core's settings.
- Unknown keys throw, and values are validated against the definition before they are stored.

## Static analysis

- The core package is analysed at Larastan level 7 (`phpstan-core.neon`).
- The shop's own code (`app/`, `extensions/`) is analysed at level 5 (`phpstan.neon`).

## The storefront build

- **Source:** the storefront's source is in the package (`resources/js`, `resources/css`). Vite builds it with the package as its root, so entries stay `resources/js/app.tsx` wherever the core is installed.
- **Developing:** `npm run dev` and `npm run build` write the project's own `public/build`, which the storefront prefers whenever it exists.
- **Releases:** `npm run build:core` writes the prebuilt storefront to `theme/dist` in the package. It is published to `public/vendor/pnshop/build` by `composer update` (the `laravel-assets` tag), `pnshop:install` and `pnshop:update`.
- **Release trees:** `scripts/release/build-dist.sh <dir>` builds the package and the `pnscripts/pn-shop` skeleton from a commit.

CI runs both.
