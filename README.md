# PN Shop

An open-source **Laravel 13 + React/Inertia** e-commerce application, on its way to a full CMS and e-commerce platform. See [docs/](docs/README.md) for the architecture and roadmap.

Today it is a working shop: catalog with variants, a database cart, guest or authenticated checkout with structured addresses, customer accounts with address books, and a Filament admin.

This is **not** a full marketplace. Card payments come through the included Stripe plugin. There is no public REST API yet.

---

## What is implemented

- Public storefront
  - Home with featured/active products (`GET /`)
  - Shop index with optional category filter and pagination (`GET /shop`)
  - Product page by slug (`GET /shop/{slug}`)
  - Cart kept in the database: survives the session for guests, follows customers across devices, merges on sign-in
  - Checkout (guest or signed-in): shipping and billing addresses, saved addresses, payment method, totals breakdown
  - Customer account: orders and address book (`/dashboard`, `/account/orders`, `/account/addresses`)
  - Honeypot and time-trap spam protection on checkout and registration
  - Order confirmation (`GET /orders/{order}`) for the owner or the current session
- Auth from the official Laravel React starter (register, login, password reset, profile)
- Admin panel at `/admin` (Filament 5) with separate staff accounts, roles and permissions
  - Dashboard: today's orders, pending orders, latest orders, low stock
  - Products with variants (options such as size), gallery images, brands, nested categories, filterable attributes, related products/upsells/cross-sells
  - Inventory with a stock movement ledger; media library with WebP conversions
  - Orders: numbers, status / payment / fulfillment state machines with history and notes (stock is reserved at checkout, taken when shipped, released on cancel)
  - Customers with address books and order history; customer groups
  - CMS pages built from content blocks (with revisions and scheduling), header and footer menus
  - SEO: meta tags, hreflang, JSON-LD, sitemaps, generated robots.txt, automatic 301 redirects
  - Plugins: install / enable / update / disable / uninstall from the admin or CLI, safe mode, signatures; storefront slots and blocks for plugins; Stripe and a handling-fee reference plugin included
  - Themes: prebuilt storefront bundles, child themes that override files by path, theme settings as CSS variables; the Aurora example theme
  - Promotions built from conditions and discounts (percent, fixed, buy X get Y, free shipping), coupons with bulk codes and usage limits
  - Return requests (RMA): customers request returns from the order page; staff approve, receive (with restocking) and refund
  - Payment methods (cash on delivery, bank transfer) with a payment ledger; shipping zones and methods (flat, free, pickup, by weight, by subtotal) with partial shipments and tracking; refunds with restocking; numbered invoices; order emails in the customer's language; tax classes, zones and rates (inclusive or exclusive prices)
  - Admin users, roles, store settings, activity log
- JSON APIs ([docs/api](docs/api/README.md)), both with problem+json errors, cursor pagination and OpenAPI documents:
  - **Store API** (`/api/store/v1`) for headless storefronts and apps: catalog, pages, menus, cart, idempotent checkout, customer accounts.
  - **Admin API** (`/api/admin/v1`) for integrations: staff tokens limited to chosen permissions.
- Installer (`php artisan pnshop:install` or the web installer at `/install`) and updater (`php artisan pnshop:update` with dry run, backup and plugin compatibility checks); tested on SQLite and MySQL 8
- The core as the Composer package `pnscripts/pn-shop-core` (in this repository: `packages/pn-shop-core`), with the storefront prebuilt; shops are thin `pnscripts/pn-shop` projects updated with `composer update` and `php artisan pnshop:update`. See [docs/development/core-modules.md](docs/development/core-modules.md)
- English and Bulgarian storefront with a language switcher, localized URLs (`/bg/...`) and translatable catalog content
- Built-in payment gateways are manual (cash on delivery, bank transfer); card gateways come as extensions
- PHPUnit feature/unit tests for models, cart, shop flow, and admin access

## Not in 1.0

Multi-store and sales channels in the admin, multi-location inventory screens, B2B price lists, a visual page-builder canvas, GraphQL and out-of-process apps are planned after 1.0. See [CHANGELOG.md](CHANGELOG.md) for what 1.0 contains.

---

## Stack

- Laravel 13, PHP 8.4+
- Inertia 3, React 19, TypeScript 5.9, Vite 8
- Tailwind CSS 4.3, shadcn/Radix UI
- Node 22.12+ for building assets (24 LTS recommended, see `.nvmrc`)
- SQLite by default (MySQL/PostgreSQL work if you change `.env`)

---

## Install

A shop:

```bash
composer create-project pnscripts/pn-shop shop
cd shop
php artisan pnshop:install
```

From this repository (developing PN Shop itself; the core comes from `packages/pn-shop-core`):

```bash
git clone git@github.com:pnscripts/laravel-react-shop.git shop
cd shop
composer install
cp .env.example .env
php artisan key:generate
php artisan pnshop:install
npm ci && npm run build
```

The installer checks the server, sets up the database (SQLite by default, or `--db-connection=mysql …`), creates the tables, store settings and the **first administrator**. There are no default accounts. Without SSH, open the site in a browser to use the same installer at `/install`. See [docs/installation/installation.md](docs/installation/installation.md). Updates: `php artisan pnshop:update` ([docs/installation/updating.md](docs/installation/updating.md)).

For development:

```bash
php artisan pnshop:install --demo        # with demo categories and products
npm install
composer run dev                          # app server, Vite, queue worker and logs
```

`.env.example` ships production-safe defaults; for local development set `APP_ENV=local` and `APP_DEBUG=true`. More staff accounts: `php artisan pnshop:create-admin you@example.com`, or Admin → System → Admin users ([docs/administration/staff-and-roles.md](docs/administration/staff-and-roles.md)).

---

## Tests and quality checks

```bash
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse
npm run format:check
npm run lint:check
npm run types
```

CI runs all of these on every pull request, plus `composer audit` and `npm audit`.

---

## Telescope

Telescope is a development dependency. It is registered only when `APP_ENV=local`, and `TELESCOPE_ENABLED=false` by default.

---

## License

MIT © 2026 Petar Nikolov / PN Scripts
