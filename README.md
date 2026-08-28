# Laravel React Shop

An open-source **Laravel 12 + React/Inertia** e-commerce starter kit. It is a working shop MVP: catalog, product page, session cart, guest or authenticated checkout, order confirmation, and a small admin for products and orders.

This is **not** a full marketplace. There is no Stripe (or any card gateway), no roles/permissions package, and no public REST API yet.

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
- Simple admin (`is_admin` on `users`, not a roles package)
  - Products: list, create, edit, deactivate
  - Orders: list, update status
- Domain models already in the repo: `Product`, `ProductCategory`, `ProductAttribute*`, `Order`, `OrderItem`, `OrderStatus`, `PaymentMethod`, `Translation`, `ShoppingCartService`
- Product titles use the existing `HasTranslations` accessor (`title`)
- Manual payment methods only: cash on delivery and bank transfer
- PHPUnit feature/unit tests for models, cart, shop flow, and admin access

## What is not implemented

- Stripe, PayPal, or any payment gateway
- Spatie (or similar) roles/permissions — admin is a boolean `is_admin` column
- REST API and Scramble API docs (the package is still in `composer.json` from earlier work, but there are no shop API routes)
- Language switcher UI (the translation trait exists; the storefront is English)
- Category admin, attribute filtering, media uploads, order history page for customers
- Production-ready Telescope: it is disabled in `.env.example` (`TELESCOPE_ENABLED=false`)

---

## Stack

- Laravel 12, PHP 8.2+
- Official Laravel React starter with Inertia 2 and TypeScript
- Tailwind CSS 4, shadcn/Radix UI
- Session cart (`ShoppingCartService`)
- SQLite by default (MySQL/PostgreSQL work if you change `.env`)

---

## Install

```bash
git clone git@github.com:Petar-V-Nikolov/laravel-react-shop.git
cd laravel-react-shop
composer install
cp .env.example .env
php artisan key:generate
```

SQLite is the default. Create the database file if it does not exist:

```bash
touch database/database.sqlite
```

Then:

```bash
php artisan migrate --seed
npm install
npm run dev
```

In another terminal:

```bash
php artisan serve
```

Or run the bundled Composer script (app server + Vite + queue + logs):

```bash
composer run dev
```

Open `http://localhost:8000`.

### Demo credentials

After `php artisan migrate --seed`:

- Email: `test@example.com`
- Password: `password`
- This user is an admin (`is_admin = true`)

Seeded payment methods: **Cash on Delivery**, **Bank Transfer**.  
Seeded order statuses: **pending**, **paid**, **shipped**, **cancelled**.  
Product seeders create sample categories, attributes, and products.

---

## Tests

```bash
php artisan test
```

---

## Telescope

`TELESCOPE_ENABLED=false` is set in `.env.example`. Do not enable Telescope in production without locking it down.

---

## License

MIT © 2026 Petar Nikolov / PN Scripts
