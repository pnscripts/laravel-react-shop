# PN Shop on pnscripts.com: what to keep in step

This list is for whoever updates the PN Shop product page on pnscripts.com. **This repository does not change pnscripts.com.**

The page is seeded from `database/seeders/data/pn_shop_flagship.php` in the pnscripts.com repository. Its Bulgarian copy lives in the same file, and older Bulgarian strings live in `content_translations/bg.json`. Every English change needs the same change in Bulgarian.

## State checked on 2026-10-03

The English page matches PN Shop 1.1:
- an open-source CMS and e-commerce platform on Laravel 13, React 19 and Filament 5;
- MIT and free;
- the core as the Composer package `pnscripts/pn-shop-core`;
- `composer create-project pnscripts/pn-shop`, then `pnshop:install`, then `pnshop:update`;
- Stripe as a plugin.

It does not compare PN Shop with other shop platforms. That follows the owner decision of 2026-10-03.

## Still to fix on the site

- **Bulgarian copy on a fresh seed:**
  - `ContentTranslationProvisioner` only fills empty fields, and it takes the PN Shop strings from `content_translations/bg.json`. Those strings are the starter-era copy: a session cart, "a small admin", "В този магазин няма шлюз за карти" ("this shop has no card gateway"), and "Таксува ли карти? Не." ("Does it charge cards? No.").
  - The up-to-date Bulgarian block in `pn_shop_flagship.php` is applied only by migrations, and those skip a database that has no product yet.
  - **Fix:** replace those `bg.json` strings with the current Bulgarian copy, or have the seeder apply the flagship block.
- **Title pair:** `copy/2026_09_30_full_rewrite.php` still has the old title "PN Shop | Laravel 13 + React storefront" as a replacement row. It is low risk, but it is stale.

## Facts worth adding (1.1.1)

- **Security fixes** from an audit of 1.1:
  - shipped cash-on-delivery orders are no longer auto-cancelled;
  - staff cannot take over more powerful accounts;
  - a new password signs out other sessions.
- **Trusted proxies** (`TRUSTED_PROXIES`) for shops behind a load balancer or Cloudflare.
- **Optional email verification** for customers.

## Things the page must not claim

- **Not built yet:** a visual page builder, several stores on one installation, B2B price lists, stock in several locations, PayPal. All are planned, none is released.
- **No dependency on pnscripts.com:** PN Shop does not depend on pnscripts.com, and the Marketplace stays a separate product.
- **No comparisons:** no comparison with, or reference to, other shop platforms.
