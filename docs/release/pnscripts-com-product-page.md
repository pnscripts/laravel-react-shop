# Product page copy changes for pnscripts.com (PN Shop 1.0)

The PN Shop product page on pnscripts.com describes the original starter: the "built in public" roadmap, an `is_admin` flag, and no card payments. Once 1.0 is released (on `main`, tagged), these claims are out of date. This list is for whoever updates that site. **This repository does not change pnscripts.com.**

The page's content is seeded from `database/seeders/data/pn_shop_flagship.php` in the pnscripts.com repository, with matching Bulgarian strings in the same file and in `content_translations/bg.json`. Keys are named below. Every English change needs the same change in the Bulgarian copy.

| Key / section | Now says | Should say (1.0) |
|---|---|---|
| `description`, `meta_description` | "catalog, cart, checkout, orders and admin today, growing into a full CMS and e-commerce platform" | "A self-hosted CMS and e-commerce platform on Laravel and React: catalog with variants, CMS pages, promotions, payments, shipping, tax, returns, plugins, themes and APIs." |
| `solution_summary` | "grows in public, phase by phase, toward a modular platform" | "A modular platform in the spirit of OpenCart and PrestaShop, built on Laravel 13, React 19 and Filament 5." |
| `value_proposition` | "Install it, place a test order… The roadmap in the repository shows each phase as it lands." | "Install it with `php artisan pnshop:install` (or the web installer), add products in the admin and take orders. Updates run with `php artisan pnshop:update`." |
| `roadmap_summary` | "Built in public toward a 1.0 release: …" | Replace with what 1.0 contains, or with the post-1.0 plans: multi-store, B2B price lists, a visual page builder. |
| Hero hint (BG `hint`) | "Примерна количка. Все още без картови плащания." ("Sample cart. No card payments yet.") | Remove "no card payments yet": Stripe ships as a plugin. |
| Feature cards ("The whole purchase flow", "Orders saved…", "Tested and checked in CI") | Describe the starter | Suggested cards: **Catalog and CMS** (variants, inventory, pages from blocks, SEO); **Selling** (Stripe and offline payments, shipping zones, tax, promotions and coupons, returns); **Extensible** (plugins, child themes, Store and Admin APIs); **Tested** (CI on SQLite, MySQL and PostgreSQL). |
| Roadmap `lead` | "Only the first column is in the release you can install today." | Remove the phased roadmap, or mark every column as released. |
| FAQ "What can it do today?" | "A catalog with category filtering… a small admin for products and orders." | Summarise the 1.0 feature list (see CHANGELOG.md). |
| FAQ "Is it a full e-commerce platform like OpenCart or PrestaShop?" | "Not yet; that is where it is heading." | "Yes for a single store: catalog, CMS, checkout, payments, shipping, tax, promotions, returns, plugins, themes and APIs. Multi-store and B2B price lists come after 1.0." |
| FAQ on payments | "Cash on delivery and bank transfer. There is no Stripe, PayPal or other card gateway yet." | "Cash on delivery and bank transfer are built in. Card payments come as plugins; Stripe Checkout is included. Other gateways can be added with the payment gateway contract." |
| FAQ on the admin/roles ("not in the released code yet") | Roles are "in development" | "A Filament admin with separate staff accounts, roles and permissions, and API tokens limited to chosen permissions." |
| FAQ "How is the admin protected?" | "With an is_admin flag on the user account." | "Staff accounts are separate from customer accounts, with roles and permissions. There are no default accounts; the installer creates the first administrator." |
| FAQ requirements | "PHP 8.4…, Node 22.12… to build the assets…, SQLite by default. MySQL and PostgreSQL also work" | "PHP 8.4+ with intl, bcmath and sodium; MySQL 8, MariaDB, PostgreSQL or SQLite. Node is only needed to build themes from source." |
| FAQ on the Marketplace | Plugins "may be listed once its extension…" | Unchanged in substance. PN Shop has its own plugin and theme format; Marketplace listing remains a separate decision. |

Things the page must not claim:

- **Visual page builder, multi-store, B2B price lists, PayPal:** none of these is in 1.0.
- **Ties to pnscripts.com:** PN Shop does not depend on pnscripts.com, and the Marketplace stays a separate product.
