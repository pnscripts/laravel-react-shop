# PN Shop

An open-source **Laravel 13 + React/Inertia** e-commerce application, on its way to a full CMS and e-commerce platform. See [docs/](docs/README.md) for the architecture and roadmap.

Today it is a working shop MVP: catalog, product page, session cart, guest or authenticated checkout, order confirmation, and a small admin for products and orders.

This is **not** a full marketplace. There is no Stripe (or any card gateway), and no public REST API yet.

---

## What is implemented

- Public storefront
  - Home with featured/active products (`GET /`)
  - Shop index with optional category filter and pagination (`GET /shop`)
  - Product page by slug (`GET /shop/{slug}`)
  - Session cart: add, update quantity, remove
  - Checkout (guest or signed-in): name, email, phone, address, payment method
  - Order confirmation (`GET /orders/{order}`) for the owner or the current session
- Auth from the official Laravel React starter (register, login, password reset, profile)
- Admin panel at `/admin` (Filament 5) with separate staff accounts, roles and permissions
  - Dashboard: today's orders, pending orders, latest orders, low stock
  - Products with variants (options such as size), gallery images, brands, nested categories, filterable attributes, related products/upsells/cross-sells
  - Inventory with a stock movement ledger; media library with WebP conversions
  - Orders: list, view, change status (cancelling returns stock)
  - Admin users, roles, store settings, activity log
- Domain models already in the repo: `Product`, `ProductCategory`, `ProductAttribute*`, `Order`, `OrderItem`, `OrderStatus`, `PaymentMethod`, `Translation`, `ShoppingCartService`
- Product titles use the existing `HasTranslations` accessor (`title`)
- Manual payment methods only: cash on delivery and bank transfer
- PHPUnit feature/unit tests for models, cart, shop flow, and admin access

## What is not implemented

- Stripe, PayPal, or any payment gateway
- REST API
- Language switcher UI (the translation trait exists; the storefront is English)
- Order history page for customers

---

## Stack

- Laravel 13, PHP 8.4+
- Inertia 3, React 19, TypeScript 5.9, Vite 8
- Tailwind CSS 4.3, shadcn/Radix UI
- Node 22.12+ for building assets (24 LTS recommended, see `.nvmrc`)
- Session cart (`ShoppingCartService`)
- SQLite by default (MySQL/PostgreSQL work if you change `.env`)

---

## Install

```bash
git clone git@github.com:pnscripts/laravel-react-shop.git
cd laravel-react-shop
composer install
cp .env.example .env
php artisan key:generate
```

`.env.example` ships production-safe defaults. For local development set `APP_ENV=local` and `APP_DEBUG=true` in `.env`.

SQLite is the default. Create the database file if it does not exist:

```bash
touch database/database.sqlite
```

Then:

```bash
php artisan migrate --seed
php artisan storage:link
npm install
npm run dev
```

Image conversions run on the queue: `composer run dev` starts a worker, and production needs `php artisan queue:work`.

In another terminal:

```bash
php artisan serve
```

Or run the bundled Composer script (app server + Vite + queue + logs):

```bash
composer run dev
```

Open `http://localhost:8000`.

### Administrator account

No accounts are seeded. Create an administrator after migrating, then sign in at `/admin`:

```bash
php artisan pnshop:create-admin you@example.com --name="Your Name"
```

The command asks for a password (or use `--generate-password` to print a random one once). Staff accounts are separate from customer accounts; see [docs/administration/staff-and-roles.md](docs/administration/staff-and-roles.md).

Seeded payment methods: **Cash on Delivery**, **Bank Transfer**.  
Seeded order statuses: **pending**, **paid**, **shipped**, **cancelled**.  
Outside production, the seeders also create sample categories, attributes and products.

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
