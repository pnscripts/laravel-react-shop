# Platform Architecture Research for PN Shop

Date: 2026-10-01. Scope: architecture only, no code copied. Target: Laravel 13 + Inertia + React 19 CMS/e-commerce with plugin and theme ecosystems.

Legend: **[V]** = verified against official docs/release pages during this research (URL in Sources). **[M]** = from memory / long-standing documented behaviour, not re-verified today; treat details as "very likely correct, check before relying on exact names".

---

## 0. Current versions (as of 2026-10-01)

| Platform | Current stable | Stack | Notes |
|---|---|---|---|
| OpenCart | 4.1.0.4 (2026-08-11) [V] | Custom MVC-L PHP, Twig | PHP 8.5 support; OCMOD re-introduced in 4.1 but not XML-based anymore [V] |
| PrestaShop | 9.2.0 (9.1.5 maintenance 2026-08-18) [V] | Symfony 6.4 LTS + legacy ObjectModel, Smarty front / Twig back | 9.2 adds `services.php` + version-specific services files for module BC [V] |
| WooCommerce | 10.8 (May 2026), monthly cadence [V] | WordPress plugin | HPOS default for new stores; block Cart/Checkout replace shortcodes [V] |
| Shopware 6 | 6.7.14.2 (2026-09-23) [V] | Symfony 7.4, Vue admin, Twig storefront | Plugins vs Apps split; Store API caching expanded in 6.7.6 [V] |
| Magento OS / Adobe Commerce | 2.4.9 [V] (2.4.8 still supported) | Zend/Laminas + own DI, Knockout/RequireJS | PHP 8.5, OpenSearch 3, Symfony 7.4 components, TinyMCE→HugeRTE [V] |
| Bagisto | 2.4.10 stable; 2.5.0-beta3 (2026-09-17) [V] | Laravel 12, Vue, Tailwind, Concord modules | Laravel AI SDK, RMA, API Platform-based REST/GraphQL [V] |
| Lunar | 1.x (1.3.0 Jan 2026; later 1.x on Filament v4) [V] | Laravel package, Filament admin, headless | Headless-first [V] |
| Aimeos | 2025.x/2026.x LTS line [M] | Laravel package (aimeos-laravel), own framework | Very large, JSON:API frontend API [M] |

---

## 1. Extension / plugin systems

### 1.1 Comparison table

| | Package format | Manifest | Discovery | Lifecycle | Hook model | Trust |
|---|---|---|---|---|---|---|
| **OpenCart 4** | `name.ocmod.zip` uploaded via admin installer, extracted to `extension/<code>/` [V] | `install.json`: name, version, author, link — no compat/deps fields [V] | Filesystem scan of `extension/*/admin/controller/<type>/` [M] | Upload → Install (calls controller `install()`) → per-type enable via settings `status` → Uninstall → delete [M] | **Events** table (`event` rows: trigger route pattern `catalog/view/.../before`, action route) registered by extension at install [M]; OCMOD 4.1 = code-modification layer [V] | Full PHP execution, no signing |
| **PrestaShop 9** | Zip of module folder → `modules/<name>/`; Composer autoload inside [V] | Main class properties: `name`, `version`, `ps_versions_compliancy` (min/max), `dependencies`; auto-generated cached `config.xml` [V] | Folder scan + Addons marketplace | `install()`/`uninstall()`/`enable()`/`disable()`/`reset`; `upgrade/upgrade-X.Y.Z.php` scripts run in version order [V] | **Hooks** (`registerHook('displayX'/'actionX')`): display hooks return HTML, action hooks mutate; plus Symfony services/decoration; `override/` (discouraged) [V] | Full PHP; Addons validator for marketplace only |
| **WooCommerce** | WP plugin zip / Composer (wpackagist) | Plugin header comment: Name, Version, Requires at least, Requires PHP, `WC requires at least`, `WC tested up to`, `Requires Plugins` (WP 6.5+) [M]; feature compat declared in code (HPOS, cart/checkout blocks) [V] | `wp-content/plugins` scan | activate/deactivate/uninstall hooks; *no* built-in migration runner — plugins version-check an option and run dbDelta [M] | **Actions/filters** (`do_action`/`apply_filters`) with priorities; Store API extensibility (`ExtendSchema`) for blocks [M] | Full PHP; WP.org review only |
| **Shopware 6 Plugins** | Composer package `type: shopware-platform-plugin` in `custom/plugins` or vendor [M] | `composer.json` `extra.shopware-plugin-class`, label/description per locale, `require: shopware/core` constraint [M] | `plugin:refresh` | install / activate / deactivate / update / uninstall (with `keepUserData` flag) each with context object; **Migrations** as timestamped classes with `update()` + `updateDestructive()` [M] | Symfony **event subscribers**, DI **service decoration**, DAL entity extensions, Twig `sw_extends` blocks, admin Vue component override/extend [M] | Full PHP in-process; **not allowed on Shopware Cloud** [V] |
| **Shopware 6 Apps** | Folder in `custom/apps/<Name>` or zip from store [V] | `manifest.xml`: name, label, description, author, copyright, version, icon, license; plus `<setup>` (registration URL + secret), `<permissions>`, `<webhooks>`, `<admin>` modules, `<cookies>`, payments, rule conditions, flow actions, CMS elements, custom fields [V] | `app:refresh`, `app:install --activate` [V] | install/activate/deactivate/update/delete, each fires lifecycle webhooks [M] | **Webhooks** (HMAC-signed), **App Scripts** (sandboxed Twig scripts run in-process on hook points), Admin Extension SDK (iframe + postMessage) [V/M] | **No arbitrary PHP**; explicit entity-level permissions (read/create/update/delete) in manifest; registration handshake exchanges secrets, Admin API via integration credentials [V] |
| **Magento 2** | Composer package `type: magento2-module` or `app/code/Vendor/Module` [M] | `registration.php` + `etc/module.xml` (name, `<sequence>` load-order deps) + composer `require` for real deps [M] | `setup:upgrade` registers modules in `app/etc/config.php` | `module:enable/disable`, `setup:upgrade` runs **declarative schema** (`db_schema.xml`) + **data/schema patches** (`DataPatchInterface`, revertable) [M] | **Plugins (interceptors)** before/after/around on any public method; **observers** on dispatched events; **DI preferences** (class rewrite); layout XML; UI components [M] | Full PHP; Marketplace EQP scanning |
| **Bagisto 2.x** | Laravel package under `packages/Webkul/<Name>/src` or Composer [V] | `composer.json` only; no dedicated manifest/compat [V] | Manual: PSR-4 autoload + provider in `bootstrap/providers.php` + Concord module in `config/concord.php` [V] | Laravel migrations; no runtime enable/disable UI in core (Composer-driven) [V/M] | Laravel **events** (`entity.create.before/after`) + **view render events** (named Blade injection points) + merged config files (`admin-menu.php`, `acl.php`, `system.php`) + Concord model proxies/overrides [V] | Full PHP; no signing |
| **Lunar** | Composer package [V] | composer only | Laravel auto-discovery | Laravel migrations | Laravel events, model extending via `ModelManifest`, Filament plugin API for admin extension, pipelines for cart calculation [M] | Full PHP |

### 1.2 Key patterns worth noting

- **Registration of admin/routes/permissions**
  - PrestaShop: `Tab` records (admin menu entries tied to controller class) installed by module; permissions auto-derived per Tab (`READ/CREATE/UPDATE/DELETE`) [M]; Symfony routes via `config/routes.yml` [V].
  - Magento: `etc/adminhtml/routes.xml`, `menu.xml`, `acl.xml` (ACL resource tree), `system.xml` (config fields) — everything declarative XML merged across modules [M].
  - Bagisto: same idea with PHP arrays: `admin-menu.php`, `acl.php`, `system.php` merged into core config [V]. This is the most Laravel-idiomatic version of Magento's merged declarative config.
  - Shopware: admin modules registered in JS (`Module.register`) for plugins, or via `<admin><module>` in manifest for apps (iframe) [M/V]; ACL roles via `privileges` added in admin JS [M].
- **Failure/rollback**
  - Magento: data patches can implement `PatchRevertableInterface`; declarative schema computes diff, so failed/partial upgrades are re-runnable; maintenance mode during `setup:upgrade` [M].
  - Shopware: lifecycle runs in transaction where possible; `updateDestructive` migrations separated so blue-green deploys can run non-destructive first [M]. This split is an excellent idea.
  - PrestaShop/OpenCart/Woo: mostly none — a failing `install()` returns false and leaves partial state. Common pain point.
- **Compatibility declaration**: PrestaShop (`ps_versions_compliancy`), Woo (`WC requires at least`/`tested up to` + per-feature compat flags like HPOS — WooCommerce *auto-disables a feature* when an active plugin hasn't declared compat [V]), Shopware (composer constraint on `shopware/core`). The Woo feature-compat flag is a clever pattern for platform evolution.
- **Load-order dependencies**: Magento `<sequence>`, Shopware composer requires, PrestaShop `dependencies`. OpenCart has none.

### 1.3 Trust/security spectrum

1. **In-process arbitrary PHP** (OpenCart, PrestaShop, Woo, Magento, Bagisto, Lunar, Shopware Plugins) — maximal power, zero isolation, code is fully trusted.
2. **Sandboxed in-process scripting** (Shopware App Scripts: Twig-based, restricted services, run on defined hooks) — moderate power, safe for SaaS [M].
3. **Out-of-process apps** (Shopware Apps, Shopify-style): manifest-declared permissions, signed webhooks, OAuth/integration credentials to Admin API, iframe admin UIs [V].

Shopware is the only one in this set that offers both 1 and 3 side by side; it's the key reference if PN Shop ever wants a hosted/SaaS mode or a marketplace with untrusted vendors.

---

## 2. Theme systems

| | Inheritance | Overrides | Settings | Assets |
|---|---|---|---|---|
| OpenCart 4 | Theme extension type; fallback to `default` [M] | Twig file override; theme editor stores overrides in DB (`theme` table) [M] | Per-store settings | No build pipeline in core |
| PrestaShop 9 | Parent/child via `theme.yml` `parent:` key [M] | Smarty templates in theme override module templates at `themes/<t>/modules/<module>/...` [M] | `theme.yml`: hooks layout positions, image types, page layouts, module list to enable [M] | Webpack in classic theme; Hummingbird theme (Bootstrap 5) in 9.x [M] |
| WooCommerce | WP parent/child themes; block themes (FSE) with `theme.json` [M] | `yourtheme/woocommerce/<template>.php` overrides with version headers that warn on outdated overrides [M] | Customizer / Site Editor global styles | Theme-specific |
| Shopware 6 | Theme = plugin/app with `theme.json`; `views` inheritance chain (`@Storefront`, `@Plugins`, own) [M] | Twig `sw_extends` + `{% block %}` overriding (multi-inheritance across plugins) [M] | `theme.json` `config.fields` (colors, media, typed fields) editable per sales channel in admin; compiled into SCSS variables [M] | `theme:compile` builds SCSS/JS per sales-channel+theme [M] |
| Magento 2 | `theme.xml` `<parent>`, unlimited chain (Luma→Blank) [M] | Template/layout XML fallback by module path; layout XML merging [M] | `etc/view.xml` (image sizes, vars) | LESS compile + static content deploy; Hyvä (Alpine+Tailwind) is the de-facto modern frontend [M] |
| Bagisto 2 | `config/themes.php` maps theme code → `views_path`, `assets_path`, `parent` [M] | Blade view path fallback; view render events [V] | Theme customization in admin (sliders, static content, footer links) [M]; Bagisto Visual adds visual editor [V] | Vite per theme [M] |

**Takeaways**: (a) Settings declared in a theme manifest with typed fields, editable per channel, compiled into CSS variables (Shopware) is the cleanest model. (b) Woo's "template version header + outdated override warning" is a cheap, high-value safety net. (c) Block-level overriding (Twig blocks) beats whole-file overriding for upgrade safety — the React analogue is **named slot/component registry** rather than file replacement.

---

## 3. Catalog model

| | Product types | Attributes | Variants | Inventory | Pricing | Categories | Brands |
|---|---|---|---|---|---|---|---|
| OpenCart | Single type; "options" (select/radio/checkbox/text/file/date) with price/weight modifiers; "variant" products introduced in 4.x (master + variant linkage) [M]; subscriptions [V] | Attribute groups (spec display only) [M] | Options (non-SKU) | Single qty per product/option value | Customer-group discounts (qty tiers), specials with date ranges, reward points [M] | Adjacency + `category_path` closure table [M] | Manufacturers |
| PrestaShop | Standard, pack, virtual; combinations [M] | Features (spec) vs Attributes (variant axes) — explicit split [M] | **Combinations** = SKU-level variants with own ref/EAN/price impact/stock [M] | StockAvailable per product/combination/shop; advanced stock mgmt removed in 1.7 era [M] | **Specific prices** (per shop/currency/country/group/customer/qty/date) + catalog price rules [M] | Nested set (`nleft`/`nright`) [M] | Manufacturers + suppliers |
| WooCommerce | simple, variable, grouped, external; virtual/downloadable flags [M] | Global attributes (taxonomies) + local per-product [M] | Variations are child posts (`product_variation`) [M] | Single stock per product/variation | regular/sale price with schedule; tiered via plugins | WP taxonomy (adjacency) | Brands taxonomy now core (9.x) [M] |
| Shopware 6 | Product + variants (parent/child inheritance of fields), digital products (6.5+), bundles via plugin [M] | **Property groups** (filterable) + **custom fields** (JSON) [M] | Variants generated from configurator settings; child inherits parent fields unless overridden [M] | Single stock (`stock`/`available_stock`) by default; multi-warehouse in commercial [M] | **Advanced prices** bound to **Rule Builder** rules (any condition: customer group, cart, date…), per currency, qty tiers [M] | Adjacency + materialized `path` string [M] | Manufacturers |
| Magento 2 | simple, configurable, grouped, bundle, virtual, downloadable, gift card (Commerce) [M] | **EAV** + attribute sets/groups; scope (global/website/store view) per attribute [M] | Configurable = parent + simple children linked by super attributes [M] | **MSI**: sources, stocks, source selection algorithm, reservations [M] | Tier prices, customer group prices, special price, catalog price rules (indexed), shared catalogs (B2B) [M] | Adjacency + `path` + `level`, index tables [M] | Attribute only (no entity) |
| Bagisto | simple, configurable, virtual, grouped, downloadable, bundle, booking [M] | **Attribute families** → groups → attributes, values stored in `product_attribute_values` (EAV-style, typed columns) + flat table for listing [M] | Configurable super attributes → child simple products [M] | **Inventory sources** (multi-warehouse) per channel [M] | Customer-group prices (qty tiers), special price with dates, catalog rules, cart rules [M] | **Nested set** (kalnoy/nestedset) [M] | Via attribute |
| Lunar | Products with **product types** (schema of attribute fields), **variants** always present [M] | Attributes stored as **JSON `attribute_data`** with typed field classes (Text, TranslatedText, etc.) [M] | Product options/option values → variants | Stock per variant | **Prices** table polymorphic (priceable), per currency + customer group + qty tier [M] | Collections (nested set) in collection groups [M] | Brands |

**Takeaways**: Converged consensus = *every sellable thing is a variant/SKU*, parent product holds shared content; PrestaShop's **features (spec) vs attributes (variant axis)** split is clear; Shopware's **parent→child field inheritance** avoids duplication; Lunar's **JSON attribute_data with typed field casts** is the modern alternative to EAV; Magento MSI's **reservations** model avoids stock locking contention.

---

## 4. Checkout & orders

| | Cart | Order states | Payment abstraction | Shipping | Tax | Discounts |
|---|---|---|---|---|---|---|
| OpenCart | DB `cart` table keyed by session/customer [M] | Admin-configurable order statuses (flat list), status history [M] | Payment extension type: `getMethods()`, `confirm()` controller; no unified capture/refund API [M] | Shipping extension `getQuote()` [M] | Tax classes → tax rates per geo zone, rules by shipping/payment/store address [M] | Coupons, vouchers, "total" extensions pipeline (sorted order totals) [M] |
| PrestaShop | `cart` table persistent, cart rules applied [M] | **Order states** configurable with flags (paid, shipped, invoice, send email, logable), history [M] | PaymentModule base + `paymentOptions` hook; refunds partly manual [M] | Carriers with ranges (weight/price) per zone, or module carriers [M] | Tax rules groups → tax rules per country/state/zip; per-shop incl/excl display [M] | **Cart rules** (conditions + actions, priority, combinability) + catalog price rules [M] |
| WooCommerce | Session + persistent cart in user meta; Store API cart for blocks [M] | Fixed statuses (pending, processing, on-hold, completed, cancelled, refunded, failed) + custom via filters [M]; HPOS tables `wc_orders`, addresses, operational data, meta [V] | `WC_Payment_Gateway` class: `process_payment`, `process_refund`, `supports[]` (refunds, tokenization, subscriptions); Blocks payment registration in JS [M] | Shipping zones → methods (instances) [M] | Tax classes + rates table by country/state/postcode/city, compound/priority [M] | Coupons (fixed cart/percent/product) |
| Shopware 6 | Cart persisted (`cart` table, serialized), **cart processors/collectors pipeline** [M] | **State Machine** entity: separate machines for order, order_delivery, order_transaction; transitions defined in DB, events per transition [M] | Payment handlers: sync/async/prepared/refund/recurring interfaces (unified `AbstractPaymentHandler` in 6.7) [M]; apps define payment via manifest URLs [V] | Shipping methods with price matrix bound to Rule Builder rules [M] | Tax entity + tax rules per country/state/zip; **tax providers** (apps can compute) [V]; gross/net per customer group [M] | **Promotions** (rule-based conditions, discounts on cart/set groups, individual codes) [M] |
| Magento 2 | **Quote** (persistent DB) → converted to order [M] | Order **states** (fixed: new, pending_payment, processing, complete, closed, canceled, holded) + mapped custom **statuses** [M] | **Payment Gateway command pool** (authorize, capture, void, refund, request builders, transfer factory, response validators/handlers) [M] — most rigorous | Carrier models (`collectRates`), table rates [M] | Tax classes (product & customer) + rates + rules; catalog prices incl/excl config; FPT [M] | Cart price rules (conditions tree, coupon generation) + catalog rules |
| Bagisto | `cart` tables persistent [M] | Statuses: pending, processing, completed, canceled, closed, fraud; invoices/shipments/refunds as entities [M] | Payment methods via `config/payment_methods.php` + class extending `Payment` abstract (`getRedirectUrl`, etc.) [M] | Carriers in `config/carriers.php` extending `AbstractShipping::calculate()` [M] | Tax categories → tax rates (country/state/zip) [M] | Cart rules + catalog rules [M] |
| Lunar | DB cart with **calculation pipelines** (configurable pipeline classes) [M] | Order statuses in config (`lunar.orders.statuses`) [M] | **Payment drivers** via manager (`Payments::driver('stripe')->cart()->authorize()`/`capture`/`refund`) [M] | **Shipping modifiers** add options to cart [M] | Tax driver interface (default system tax via tax zones/classes/rates) [M] | Discount types as classes [M] |

**Takeaways**: (a) Separate **state machines** for order, payment (transaction), fulfilment/delivery (Shopware) is the correct granularity. (b) Magento's command-pool gateway and Lunar's Laravel **Manager + driver** pattern define the payment contract; adopt the Lunar shape with Magento's verb set (authorize/capture/void/refund/webhook). (c) Totals/cart **pipeline** (OpenCart totals, Shopware processors, Lunar pipelines) is universal — make it explicit and extensible. (d) Store **all monetary amounts as integer minor units** with currency (Lunar, Shopware use this or decimals with care).

---

## 5. CMS

| | Pages | Blocks/widgets | Page builder | Menus | Media | SEO |
|---|---|---|---|---|---|---|
| OpenCart | Information pages [M] | Module positions per layout (content_top, column_left…) via layout routes [M] | None | Category top menu | File manager (basic) | SEO URL table (keyword → key/value), meta per entity [M] |
| PrestaShop 9 | CMS pages + categories [M] | Widgets = modules implementing `WidgetInterface` placed on display hooks [M] | None in core (3rd-party Creative Elements etc.) | ps_mainmenu module | Basic | Friendly URLs via route patterns + per-entity `link_rewrite`; meta pages; sitemap module [M] |
| WooCommerce | WP pages/posts | Blocks (Gutenberg) | Gutenberg / FSE [M] | WP nav menus/Navigation block | WP Media Library | Permalinks; SEO via Yoast etc. |
| Shopware 6 | **Shopping Experiences** layouts: page → sections (layout type: default/sidebar) → blocks → slots → **elements**; layouts assignable to category/product/landing pages [M]; apps can define CMS elements & blocks [V] | Elements (text, image, product box, slider, listing...) with config + data resolvers [M] | Built-in | Category tree = navigation | Media manager with folders, thumbnails config, private media [M] | **SEO URL templates** (Twig per route, per sales channel), canonical, `seo_url` table, sitemap generation, meta per entity [M] |
| Magento 2 | CMS pages [M] | CMS blocks, widgets (instances bound to layout handles) [M] | **Page Builder** (rows → columns → content types; HTML stored with data-attributes) [M] | Category-based | Media gallery + Adobe Stock | `url_rewrite` table (request_path → target_path, store-scoped, auto-generated + custom), sitemap, canonical [M] |
| Bagisto | CMS pages (translatable) [M] | Theme customizations (sliders, static content) [M] | Bagisto Visual (separate package) [V] | Admin-managed | Basic | URL keys per entity + meta; sitemap [M] |
| Statamic/Filament | Statamic: collections/entries, blueprints/fieldsets (schema-defined), **Bard/Replicator sets** = structured block content stored as JSON [M]; Filament: Builder field (typed blocks → JSON) [M] | — | Structured JSON blocks | Navigation structures | Asset containers (Statamic) [M] | Statamic SEO Pro addon |

**Takeaways**: Store page content as **typed JSON block trees** (Statamic Bard/Replicator, Filament Builder, Shopware sections→blocks→elements) with a server-side **data resolver per block type** (Shopware) so blocks like "product slider" fetch data in a batched way. Avoid Magento Page Builder's HTML-with-data-attributes storage. Adopt Magento/Shopware's dedicated **`url_rewrites` table** (request_path, target type/id, locale, channel, redirect flag) with templated generation.

---

## 6. Localization, currency, multi-store

| | Translations | Currency | Multi-store |
|---|---|---|---|
| OpenCart | `*_description` tables per language_id [M] | Currencies with rates, auto update | Multi-store via `store_id` on settings & `*_to_store` tables [M] |
| PrestaShop | `*_lang` tables (id, id_lang, id_shop) [M] | Currencies per shop, CLDR formatting [M] | **Multistore** shop groups/shops, `*_shop` association tables, context-based config [M] |
| WooCommerce | Gettext for UI; content via WPML/Polylang [M] | Single currency core [M] | WP Multisite |
| Shopware 6 | `<entity>_translation` tables with **language inheritance/fallback** (child language → parent) [M] | Currency per sales channel, rounding config per currency [M] | **Sales channels** (storefront, headless, product comparison) each with domains, languages, currencies, payment/shipping, theme [M] |
| Magento 2 | EAV values per store view (scope) [M] | Base/display currencies per website [M] | **Website → Store (group) → Store View** hierarchy with config scopes default/website/store [M] |
| Bagisto | `*_translations` tables (astrotomic/laravel-translatable) + locale [M] | Multi-currency with exchange rates [M] | **Channels** with own locales/currencies/inventory sources/theme [M] |
| Lunar | JSON translated attributes (`TranslatedText`) [M] | Currencies, prices per currency [M] | **Channels** (scheduling availability per channel) [M] |

**Takeaways**: Two valid options: translation tables (Shopware/Bagisto/PrestaShop — queryable, indexable, sortable by translated name, clean FK) vs JSON columns (spatie/laravel-translatable, Lunar — simpler, weak for search/sort on large catalogs). For core catalog/CMS entities in a platform that must scale and support search indexing, **translation tables with fallback chain** win. A **Channel** concept (Shopware/Bagisto/Lunar) is simpler than Magento's 3-level hierarchy and sufficient.

---

## 7. APIs

| | Storefront API | Admin API | Auth |
|---|---|---|---|
| OpenCart | Minimal legacy `api/` routes [M] | Same | API key + IP whitelist session [M] |
| PrestaShop 9 | Webservice (XML/JSON) legacy [M] | **New Admin API** (API Platform–based, OAuth2 client credentials, scopes) introduced in 9.0 [M] | API keys (legacy), OAuth2 (new) |
| WooCommerce | **Store API** (`/wc/store/v1`, unauthenticated + nonce/cart token) [M], improved in 10.7 [V] | REST `/wc/v3` (+ v4 emerging) [V] | Consumer key/secret, app passwords |
| Shopware 6 | **Store API** — customer-facing, public with `sw-access-key` header, `sw-context-token` for customer/cart [V/M] | **Admin API** — OAuth2 (client credentials via integrations, password grant for admin user), full DAL CRUD + **Sync API** for bulk upserts [V/M] | Separate keys per sales channel; integrations with ACL roles |
| Magento 2 | REST + **GraphQL** (storefront-focused) [M] | REST (admin token / integration OAuth) [M] | Bearer tokens, integrations |
| Bagisto | REST + GraphQL via API Platform (2.4+) [V] | REST admin endpoints [M] | Sanctum [M] |
| Lunar | Headless; you build API [V] | Filament | — |
| Aimeos | JSON:API frontend + admin JSON:API/GraphQL [M] | | |

**Takeaway**: Shopware's split is the reference: a **Store API** (per-channel access key, context token for cart/customer, cacheable, everything the storefront does) and an **Admin API** (OAuth2/token, permission-scoped, bulk sync). Make the Inertia storefront consume the same application services as the Store API so headless is not a second-class citizen.

---

## 8. Install / update

| | Install | Update | Backups |
|---|---|---|---|
| OpenCart | Web installer (`/install`) + CLI installer [M] | Manual file replace + upgrade script; admin "upgrade" in 4.x [M] | None |
| PrestaShop | Web installer + `install/index_cli.php` [M] | **Update Assistant** (autoupgrade) module: backup files+DB, upgrade, rollback [M] | In autoupgrade |
| WooCommerce | WP plugin install | WP updater + **DB update routine** (background, Action Scheduler) [M] | External |
| Shopware 6 | Web installer (`shopware-installer.phar.php`), Composer project template, `system:setup`/`system:install` [M] | Admin auto-updater (Composer-based in 6.5+), `system:update:prepare`/`finish` [M] | None built-in |
| Magento 2 | **CLI only** (`setup:install`) — web setup wizard removed in 2.4 [M] | Composer + `setup:upgrade` [M] | Backup commands deprecated [M] |
| Bagisto | `php artisan bagisto:install` + GUI installer [M] | Composer | None |

**Takeaway**: Ship (1) a CLI `artisan pnshop:install`, (2) a thin web installer for shared hosting buyers (OpenCart/PrestaShop audience expects it), (3) updates via a versioned, resumable migration runner with pre-update DB backup + maintenance mode, run as queued jobs (Woo's Action Scheduler approach).

---

## 9. ACL / permissions

| | Model | Plugin registration |
|---|---|---|
| OpenCart | User groups with JSON lists of `access`/`modify` routes [M] | Extension install adds its route to admin group permission [M] |
| PrestaShop | Profiles → per-Tab CRUD + per-module permissions (configure/uninstall) [M] | Installing a Tab auto-creates its authorization roles [M] |
| WooCommerce | WP roles + capabilities (`manage_woocommerce`, etc.) [M] | `add_cap` on activation |
| Shopware 6 | ACL roles with privileges `entity:read/create/update/delete` + additional admin privileges [M]; apps declare permissions in manifest [V] | Plugin adds privilege mappings in admin JS; app permissions accepted at install [M/V] |
| Magento 2 | Role resources tree from merged `acl.xml` [M] | Module ships `acl.xml` |
| Bagisto | Roles with permission type all/custom, tree from merged `acl.php` [V] | Package `Config/acl.php` [V] |
| Lunar | spatie/laravel-permission integrated in admin [M] | Register permissions in service provider |

**Takeaway**: Declarative permission tree registered per plugin (Magento/Bagisto shape), backed by spatie/laravel-permission, with plugin-namespaced keys (`plugin.vendor-name.resource.action`), plus entity-level CRUD scopes for API tokens (Shopware shape).

---

## 10. Laravel-native options in brief

- **Bagisto** [V/M]: closest analogue. Uses Concord (model contracts + proxies for overriding models), repositories (prettus), merged config arrays for menu/ACL/system config, view render events, Vue/Blade themes. Weaknesses: package registration is manual (edit `bootstrap/providers.php` and `config/concord.php`), no runtime install/enable lifecycle, no compat manifest, EAV-ish attribute values with flat index tables add complexity.
- **Lunar** [V/M]: clean headless core; strong ideas — money as integers, prices polymorphic table, Manager/driver for payments, cart pipelines, `ModelManifest` for model replacement, Filament plugins for admin. No plugin lifecycle (pure Composer), no theme system (headless).
- **Aimeos** [M]: extremely capable (multi-vendor, multi-site), but own non-Laravel framework layers (decorators, manager/item/controller tiers); hard to make feel Laravel-native.
- **Statamic / Filament** [M]: reference for **schema-defined content** (blueprints/fieldsets; Filament Builder blocks) and **addon packaging** (Statamic addons are Composer packages with an `AddonServiceProvider` that auto-registers routes, fieldtypes, tags, listeners, scripts — a good model for zero-boilerplate plugin providers). Filament's `Plugin` interface (`register(Panel)`/`boot(Panel)`) is a good model for admin extension contracts.

---

## 11. Lessons for PN Shop (opinionated)

### Adopt

1. **Plugin = Composer package + manifest + ServiceProvider.** Keep Composer as the transport (autoload, dependency resolution), but add a `pnshop.json` (or `extra.pnshop` in composer.json) manifest: `id` (vendor/name), `version` (semver), `requires.pnshop` (constraint), `requires.plugins`, `requires.php`, `provides` (capabilities: payment, shipping, theme, cms-block), `permissions` (declared), `settings` schema, `features` compat flags (Woo HPOS pattern). Support zip upload for marketplace buyers that unpacks into `plugins/<vendor>/<name>` with a merged Composer path repository — but run Composer resolution in a queued job, never in a web request.
2. **Explicit lifecycle with a state table** (`plugins`: id, version, status installed/enabled/disabled/failed, installed_at, settings). Base class/contract with `install`, `enable`, `disable`, `upgrade(from, to)`, `uninstall(keepData)` (Shopware's `keepUserData`). Only **enabled** plugins have their providers booted (load list from cached manifest file, like Laravel's `bootstrap/cache/packages.php`), so a broken plugin can be disabled from CLI/safe mode without booting it.
3. **Migrations per plugin, two phases**: non-destructive (`up`) and destructive (`upDestructive`, run only on explicit uninstall or major upgrade) — Shopware's split. Track in a per-plugin migrations table. Wrap install in DB transaction where possible; on failure set status `failed`, record error, and roll back migrations already applied by that run.
4. **Safe mode**: an env flag/route that boots with all third-party plugins disabled (WordPress recovery mode analogue). Critical for an ecosystem.
5. **Hook model = Laravel events + a small set of typed extension registries**, not string hooks everywhere:
   - Domain events (immutable DTO events: `OrderPlaced`, `ProductSaved`), with `*.before` cancellable/mutable "pipeline" variants only where needed.
   - **Filter pipelines** (Laravel `Pipeline`) for well-known mutable points: cart totals, price calculation, product query, checkout validation, storefront props.
   - **Registries** for contributions: admin nav items, settings pages, permission tree, CMS block types, payment/shipping/tax drivers, dashboard widgets, storefront slots. These are the React-friendly equivalent of PrestaShop display hooks / Bagisto view render events.
   - Avoid Magento-style around-interceptors on arbitrary methods and PrestaShop `override/` class replacement. Allow container binding of **contracts** (interfaces) instead — Laravel idiom, explicit seam.
6. **Payment/shipping/tax as Manager + driver contracts** (Lunar shape): `PaymentGateway` interface with `authorize`, `capture`, `void`, `refund`, `handleWebhook`, `supports(feature)`; transactions recorded in `payment_transactions` (type, amount, status, provider reference, raw payload). Webhooks routed via `/webhooks/payments/{driver}` with signature verification in the driver and idempotency keys.
7. **Three state machines**: order, payment, fulfilment (Shopware). Define transitions in code (e.g. spatie/laravel-model-states or a small own implementation), emit events per transition, keep `order_status_history`.
8. **Catalog**: product (shared content) + variants (always ≥1, SKU-level price/stock); product types defined by **attribute schemas** (attribute sets) with typed attributes; distinguish **spec attributes** (display/filter) from **variant axes/options** (PrestaShop features vs attributes). Store attribute values in a typed table for filterable attributes + JSON for non-filterable custom fields; feed a search index (Scout + Meilisearch/OpenSearch) for faceting rather than EAV joins. Product kinds as strategy classes (simple, configurable, bundle, digital, service) registered in a registry so plugins can add types (Bagisto/Magento type-instance idea, in Laravel form).
9. **Pricing**: `prices` table (priceable morph, currency, customer_group_id nullable, min_qty, amount_minor, compare_at, starts_at/ends_at, channel_id nullable). Integer minor units + `brick/money`. Price resolution via pipeline so promotion/B2B plugins can participate.
10. **Inventory**: start with `stock_locations` + `stock_levels` (variant × location) and a **reservations** table (Magento MSI idea) — avoids a painful retrofit later; single location by default.
11. **Categories**: nested set (kalnoy/nestedset) — read-heavy trees, Bagisto/Lunar precedent.
12. **Translations**: `<entity>_translations` tables with locale + fallback chain for catalog/CMS; JSON translatable columns acceptable only for small config entities. UI strings via Laravel lang files + DB overrides.
13. **Channels**: one `channels` concept (domain(s), default locale, locales, currencies, theme, price display incl/excl, inventory locations, payment/shipping availability). Skip Magento's website/store/view hierarchy.
14. **Tax**: tax classes (product), tax zones (country/state/postcode patterns), tax rates with priority/compound, customer tax class; prices entered gross or net per channel; tax calculation behind a `TaxProvider` contract so plugins (Avalara-style) can replace it (Shopware tax providers).
15. **Promotions**: rule engine with conditions (cart subtotal, products, customer group, dates, usage limits) and actions (percent/fixed on cart/items/shipping, BOGO), priority + stop-further-rules + combinability; coupons as codes attached to rules. Model conditions as registered classes so plugins add new ones (Shopware Rule Builder lesson — one rule engine reused by prices, shipping and promotions is a powerful idea).
16. **CMS**: pages/layouts as **JSON block trees** (section → block → props) validated against block schemas; each block type = { React component (storefront), React editor form (admin), PHP data resolver, JSON schema }. Batched data resolution per page render (Shopware resolvers). Layout assignment to product/category templates.
17. **SEO**: `url_rewrites` table (channel, locale, path, target_type, target_id, is_canonical, redirect_type) generated from templates on save via queued job; automatic 301 on slug change; meta fields on translation tables; sitemap via scheduled job; JSON-LD from product/category resources.
18. **APIs**: Store API (channel key + cart/customer token via Sanctum) and Admin API (Sanctum personal access tokens / OAuth2 via Passport for integrations, permission-scoped abilities), both thin over shared action classes. Version (`/api/store/v1`, `/api/admin/v1`). Optional GraphQL later (Lighthouse) — don't start with it.
19. **ACL**: spatie/laravel-permission; plugins declare permission trees in manifest or provider registry; admin nav items and Inertia routes reference permission keys; API token abilities map to the same keys.
20. **Themes**: a theme is a plugin of type `theme` with manifest `parent`, typed `settings` schema (colors, fonts, logos → CSS variables per channel), and a **component registry override** model: storefront React pages resolve components by key (`product.card`, `checkout.summary`) through a registry that child theme/plugins can override, falling back to parent. Prebuilt assets shipped per theme/plugin (compiled Vite bundles with manifest) — do *not* require merchants to run Node in production; that is the biggest practical problem for an Inertia/React ecosystem (Shopware's `theme:compile` and Magento static deploy are the cautionary tales). Consider loading plugin bundles via import maps/dynamic `import()` from the manifest, with shared React provided as an external.
21. **Install/update**: `artisan pnshop:install` + optional web installer; update = backup → maintenance → migrations (core then plugins, resumable via queue) → cache rebuild; record versions in `system_versions`.

### Avoid

- **EAV for core catalog** (Magento): join explosion, indexers everywhere, opaque debugging. Use typed columns + translation tables + a narrow attribute-values table + search index.
- **Arbitrary class overrides / source patching** (PrestaShop `override/`, OpenCart OCMOD search-replace): breaks on every update, conflicts between plugins are undetectable.
- **Around-plugins on any public method** (Magento interceptors): powerful but makes behaviour non-local; prefer explicit contracts/pipelines.
- **Untyped string hooks returning HTML** (PrestaShop display hooks, WP actions echoing): incompatible with React/Inertia; use typed slot registries returning component keys + serializable props.
- **Storing orders in a generic content table** (Woo posts/postmeta, which they spent years migrating away from via HPOS [V]).
- **Manual plugin wiring** (Bagisto's edit-providers.php/concord.php): give plugins auto-discovery from the enabled-plugins manifest.
- **Requiring Node/Composer on the merchant's production server** for routine plugin installs.
- **Three-level store hierarchy and config scopes** (Magento) unless B2B multi-website demand is proven.

### Security / trust roadmap

- Phase 1: trusted in-process plugins (PHP), documented as full-trust; marketplace review + **signed packages** (e.g. Ed25519 signature over the zip, public key pinned in core; verify on upload) and checksum of installed files to detect tampering.
- Phase 2 (if SaaS/multi-tenant): Shopware-style **Apps**: manifest-declared permissions, HMAC-signed webhooks, scoped Admin API tokens, admin UI in sandboxed iframes communicating via postMessage SDK. Design the event system now so every domain event is serializable to a webhook payload — this makes Phase 2 cheap.

---

## Sources

Verified during this research:
- PrestaShop 9.2 release: https://build.prestashop-project.org/news/2026/prestashop-9-2-0-available/
- PrestaShop 9.1.3 / 9.1.2: https://build.prestashop-project.org/news/2026/prestashop-9-1-3-maintenance-release/
- PrestaShop core monthly Aug 2026: https://build.prestashop-project.org/news/2026/core-monthly-2026-08-01-2026-08-31/
- PrestaShop 9 module structure: https://devdocs.prestashop-project.org/9/modules/creation/module-file-structure/
- PrestaShop 9.0 module changes: https://devdocs.prestashop-project.org/9/modules/core-updates/9.0/
- OpenCart extensions docs: https://docs.opencart.com/developer-guide/extensions
- OpenCart releases: https://github.com/opencart/opencart/releases ; https://opencartbot.com/en/blog/opencart-3051-and-4104-released
- OpenCart 4.1 OCMOD forum: https://forum.opencart.com/viewtopic.php?t=235213
- WooCommerce 10.6/10.7/10.8: https://developer.woocommerce.com/2026/03/10/woocommerce-10-6-enhanced-blocks-and-a-faster-dashboard/ ; https://developer.woocommerce.com/2026/04/15/woocommerce-10-7/ ; https://developer.woocommerce.com/2026/05/12/woocommerce-10-8-pre-release/
- WooCommerce HPOS: https://developer.woocommerce.com/docs/features/high-performance-order-storage/
- Shopware releases: https://github.com/shopware/shopware/releases ; https://www.shopware.com/en/news/shopware-6-release-news-august-2026/ ; https://www.shopware.com/en/news/shopware-6-release-news-february-2026/
- Shopware extension concepts (plugins vs apps): https://developer.shopware.com/docs/concepts/extensions/
- Shopware app base guide (manifest): https://developer.shopware.com/docs/guides/plugins/apps/app-base-guide.html
- Shopware API concepts: https://developer.shopware.com/docs/concepts/api/
- Magento 2.4.9 release notes: https://experienceleague.adobe.com/en/docs/commerce-operations/release/notes/magento-open-source/2-4-9
- Bagisto releases: https://bagisto.com/en/releases/ ; https://newreleases.io/project/github/bagisto/bagisto/release/v2.4.0
- Bagisto package development (2.5 docs): https://devdocs.bagisto.com/package-development/getting-started.html
- Bagisto Visual: https://laravel-news.com/bagisto-visual-theme-framework-with-visual-editor-for-laravel-e-commerce
- Lunar: https://github.com/lunarphp/lunar/releases ; https://docs.lunarphp.com/1.x/admin/introduction

From memory (not re-fetched; check before depending on exact names): Magento DI/plugins/observers/declarative schema/MSI/Payment Gateway command pool, Shopware DAL/state machine/Rule Builder/Shopping Experiences/SEO URL templates/App Scripts, PrestaShop specific prices/cart rules/multistore/Tabs, WooCommerce gateway API and plugin headers, OpenCart events/totals/permissions, Aimeos, Statamic, Filament internals.
