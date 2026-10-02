# Changelog

All notable changes to PN Shop. The project follows [semantic versioning](https://semver.org/): breaking changes to plugin and theme contracts come only in major versions and are announced one minor version ahead.

## 1.1.0 (2026-10-02)

The core becomes a Composer package, so shops update with `composer update` and `php artisan pnshop:update`. The database does not change. See the [upgrade notes](docs/upgrades/2026-10-release-1.1.md).

### Platform

- **Core package:** `pnscripts/pn-shop-core` holds the modules, the storefront (controllers, routes, React source, the prebuilt bundle), the base migrations, seeders, views and translations. The shop project (`pnscripts/pn-shop`) keeps only the merchant's own code, configuration, plugins and themes.
- **Migration:** `php artisan pnshop:migrate-to-package` moves a 1.0 shop onto the package. It sets the old in-project files aside (it never deletes them), lists the ones the merchant changed, and checks the wiring.
- **Update checks:** `pnshop:update` refuses to run against a newer database or a `composer.lock` that disagrees with `vendor/`.
- **Prebuilt storefront:** the default storefront ships prebuilt in the package and is published on install, update and `composer update`. Shops need no Node.

### Fixes

- **`composer create-project`:** it no longer stops at a production `migrate` prompt.
- **Static analysis:** the storefront controllers pass Larastan level 7.

## 1.0.0

The first release of PN Shop as a platform: a self-hosted CMS and e-commerce system built on Laravel 13, React 19 and Inertia 3, which grew out of the Laravel React shop starter. Upgrade notes for each step are in [docs/upgrades](docs/upgrades/).

### Platform

- **Core modules:** they live in `core/` (namespace `PnShop\`), with an extension kernel: a permission registry, ordered pipelines (`cart.totals`, `seo.meta`, …) and typed, cached settings.
- **Installation:** a command-line installer (`php artisan pnshop:install`) and a web installer (`/install`), with no default accounts. Fresh installs are tested on SQLite and MySQL 8, and the test suite passes on SQLite, MySQL 8 and PostgreSQL 16.
- **Updates:** `php artisan pnshop:update` has a dry run, backups (SQLite copy, `mysqldump` or `pg_dump`, plus `.env` and optionally uploads), plugin compatibility checks, maintenance mode and a version history.
- **Admin panel:** a Filament 5 panel at `/admin`, with staff accounts separate from customers, roles and permissions, and an activity log.

### Catalog and content

- **Catalog:** products with variants (options such as size or color), each variant with its own SKU, price, sale price, weight and stock. Nested categories, brands, filterable attributes, related products, upsells and cross-sells.
- **Media library:** WebP conversions in several sizes and product galleries.
- **Inventory:** a stock ledger with reservations at checkout. Stock leaves the shelf when the order ships and comes back on cancellation and on returns.
- **CMS:** pages built from content blocks (text, image, gallery, video, hero, product and category grids, call to action, permission-gated HTML), with revisions and scheduled publishing. Header and footer menus.
- **SEO:** meta tags, hreflang, JSON-LD, sitemaps, a generated robots.txt, automatic 301 redirects when slugs change, and manual redirects.
- **Languages and money:** English and Bulgarian storefronts with localized URLs (`/bg/…`), translatable catalog and content, and currencies stored as minor units.

### Selling

- **Cart and checkout:**
  - Database carts that survive sessions and merge when the customer signs in.
  - A totals pipeline: promotions, shipping, fees, tax.
  - Structured addresses, guest and account checkout, and honeypot and time-trap spam protection.
- **Orders:**
  - Numbered orders with separate status, payment and fulfillment states, all changed through a workflow that keeps a history.
  - Partial shipments with tracking, refunds with restocking, and numbered invoices.
  - Emails in the customer's language.
- **Payments:** cash on delivery and bank transfer are built in; card payments come as plugins (Stripe Checkout is included).
- **Shipping:** zones and methods priced flat, free, pickup, by weight or by subtotal.
- **Tax:** classes, zones and rates, with tax-inclusive or tax-exclusive prices.
- **Promotions:** built from conditions (subtotal, item count, products or categories, customer group, shipping country) and discounts (percent, fixed amount, buy X get Y, free shipping).
  - Coupons can be generated in bulk, and both promotions and coupons have total and per-customer usage limits.
  - Discounts are recorded per order line, so tax and refunds use what was actually paid.
- **Returns (RMA):** customers request returns from the order page; staff approve, receive (optionally back into stock) and refund.

### Extending

- **Plugins:**
  - Installed from the `extensions/` folder or as uploaded zip archives, which are off by default.
  - Lifecycle: install, enable, update, disable and uninstall, with migrations and rollback on failure.
  - Safety: safe mode, file integrity checks and optional Ed25519 signatures.
  - What a plugin can add: admin screens, settings, permissions, pipeline stages, promotion types, content blocks and storefront slots.
- **Themes:** prebuilt storefront bundles, child themes that override files by path, and theme settings as CSS variables; Aurora is included as an example.
- **Storefront SDK (`window.PnShop`):** gives plugins named slots and content blocks without rebuilding the shop.
- **APIs:**
  - The **Store API** (`/api/store/v1`) is for headless storefronts and apps: catalog, content, cart, coupons, idempotent checkout, customer accounts and returns.
  - The **Admin API** (`/api/admin/v1`) uses staff tokens limited to chosen permissions: catalog, stock, orders, customers, pages, promotions, returns and settings.
  - Both return problem+json errors, use cursor pagination and come with OpenAPI documents in `docs/api`.

### Security and hardening

The 1.0 security review covered authorization, the APIs, input and files, and money and concurrency. See [docs/security/security.md](docs/security/security.md).

- **Stripe return page:** it no longer gives out signed order links for guessed payment ids.
- **Hosts and the installer:** the shop only answers for its own host once installed, and the installer fails closed when the database is unreachable.
- **Staff permissions:** staff cannot hand out roles or permissions they do not hold, and only administrators can edit administrators.
- **Checkout and refunds:** a double submit cannot place two orders, refunds are serialized, and per-customer promotion limits hold under concurrency on MySQL.
- **Unpaid orders:** they are cancelled automatically after a set time, which releases their stock.
- **Stripe sessions:** a cancelled order's Stripe session is expired, and late payments are flagged.
- **Guest order links:** they expire after 180 days, wrong coupon codes are rate limited, and only static plugin assets are published.

### Quality

- **CI checks:** Pint, Larastan (level 7 for `core/`), ESLint, Prettier, TypeScript, Composer and npm audits, and a bundle-size budget.
- **Tests:** PHPUnit with query-count tests on the storefront pages and API lists, and test runs on SQLite, MySQL and PostgreSQL.
