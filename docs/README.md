# PN Shop documentation

Architecture approved 2026-10-01. Phases 0–13 are implemented (1.0). The post-1.0 roadmap was approved on 2026-10-02, and 1.1 (the core as a Composer package) is in progress. The documents below record the plan, research and what has shipped.

| Document | Purpose |
|---|---|
| [architecture/01-discovery-and-gap-analysis.md](architecture/01-discovery-and-gap-analysis.md) | What exists today, what is missing, what to keep, improve or replace |
| [architecture/02-platform-architecture-proposal.md](architecture/02-platform-architecture-proposal.md) | Target architecture, decisions D1–D7, implementation phases (approval checkpoint) |
| [architecture/03-post-1.0-roadmap.md](architecture/03-post-1.0-roadmap.md) | Releases after 1.0 (core package, pricing/B2B, locations, channels, returns+), approved 2026-10-02 |
| [research/platform-comparison.md](research/platform-comparison.md) | OpenCart, PrestaShop, WooCommerce, Shopware, Magento, Bagisto, Lunar: architecture research |
| [research/version-modernization.md](research/version-modernization.md) | Current vs latest stable versions, audits, upgrade plan |
| [research/security-performance-audit.md](research/security-performance-audit.md) | Security and performance findings with file:line and fixes |
| [upgrades/2026-10-phase-0-1.md](upgrades/2026-10-phase-0-1.md) | Upgrade notes for the safety fixes and version upgrade (Phases 0–1) |
| [upgrades/2026-10-phase-2.md](upgrades/2026-10-phase-2.md) | Upgrade notes for the core foundation and admin panel (Phase 2) |
| [upgrades/2026-10-phase-3.md](upgrades/2026-10-phase-3.md) | Upgrade notes for localization and money (Phase 3) |
| [development/localization-and-money.md](development/localization-and-money.md) | Languages, localized URLs, translatable models, interface text, money |
| [upgrades/2026-10-phase-4.md](upgrades/2026-10-phase-4.md) | Upgrade notes for the catalog redesign (Phase 4) |
| [upgrades/2026-10-phase-7.md](upgrades/2026-10-phase-7.md) | Upgrade notes for the CMS, menus and SEO (Phase 7) |
| [upgrades/2026-10-phase-8.md](upgrades/2026-10-phase-8.md) | Upgrade notes for the extension system (Phase 8) |
| [upgrades/2026-10-phase-9.md](upgrades/2026-10-phase-9.md) | Upgrade notes for themes and plugin storefront code (Phase 9) |
| [upgrades/2026-10-phase-10.md](upgrades/2026-10-phase-10.md) | Upgrade notes for the Store and Admin APIs (Phase 10) |
| [upgrades/2026-10-phase-11.md](upgrades/2026-10-phase-11.md) | Upgrade notes for promotions, coupons and returns (Phase 11) |
| [upgrades/2026-10-phase-12.md](upgrades/2026-10-phase-12.md) | Upgrade notes for the installer and updater (Phase 12) |
| [upgrades/2026-10-phase-13.md](upgrades/2026-10-phase-13.md) | Upgrade notes for the 1.0 hardening (Phase 13) |
| [upgrades/2026-10-release-1.1.md](upgrades/2026-10-release-1.1.md) | Upgrade notes for 1.1: the core as the `pnscripts/pn-shop-core` package |
| [release/publishing.md](release/publishing.md) | Publishing a release: the core package and the project skeleton |
| [security/security.md](security/security.md) | Security model, hardening, production checklist |
| [release/pnscripts-com-product-page.md](release/pnscripts-com-product-page.md) | Copy changes for the PN Shop page on pnscripts.com (for whoever updates that site) |
| [installation/installation.md](installation/installation.md) | Installing: requirements, `pnshop:install`, the web installer, after installing |
| [installation/updating.md](installation/updating.md) | Updating: `composer update` + `pnshop:update`, from 1.0 to 1.1, dry run, backups, plugin compatibility |
| [ecommerce/catalog-and-inventory.md](ecommerce/catalog-and-inventory.md) | Products, variants, options, categories, brands, attributes, images, inventory |
| [upgrades/2026-10-phase-5.md](upgrades/2026-10-phase-5.md) | Upgrade notes for customers, database carts, structured checkout and stock reservations (Phase 5) |
| [ecommerce/customers-cart-and-checkout.md](ecommerce/customers-cart-and-checkout.md) | Customer groups, address books, carts, the totals pipeline, checkout, reservations, spam protection |
| [upgrades/2026-10-phase-6.md](upgrades/2026-10-phase-6.md) | Upgrade notes for orders, payments, shipping, tax, refunds, invoices and emails (Phase 6) |
| [ecommerce/orders.md](ecommerce/orders.md) | Order numbers, status/payment/fulfillment states, history, invoices, emails, OrderWorkflow |
| [ecommerce/payments.md](ecommerce/payments.md) | Payment methods, gateways, payments and transactions, writing a gateway |
| [ecommerce/shipping.md](ecommerce/shipping.md) | Shipping zones, methods and carriers, checkout delivery, shipments, writing a carrier |
| [ecommerce/returns.md](ecommerce/returns.md) | Return requests (RMA): customer form, staff workflow, restocking and refunds |
| [marketing/promotions.md](marketing/promotions.md) | Promotions (conditions and discounts), coupons, usage limits, adding rule types |
| [ecommerce/tax.md](ecommerce/tax.md) | Tax classes, zones and rates, inclusive or exclusive prices, replacing the TaxProvider |
| [cms/pages-and-blocks.md](cms/pages-and-blocks.md) | CMS pages, scheduling, revisions, the homepage, content blocks and adding block types |
| [cms/menus.md](cms/menus.md) | Header and footer menus, item types, translations, caching |
| [seo/seo.md](seo/seo.md) | Meta tags, hreflang, JSON-LD, sitemaps, robots.txt, staging mode, redirects |
| [extensions/plugins.md](extensions/plugins.md) | Plugins: trust model, manifest, lifecycle, CLI, safe mode, signatures, writing a plugin |
| [themes/themes.md](themes/themes.md) | Themes: manifest, settings as CSS variables, override-by-path builds, activation, slots |
| [api/README.md](api/README.md) | Store API and Admin API: tokens, errors, pagination, idempotency, endpoints, OpenAPI documents |
| [development/core-modules.md](development/core-modules.md) | Core module layout, permissions, pipelines and settings for developers |
| [administration/staff-and-roles.md](administration/staff-and-roles.md) | Admin panel, staff accounts, roles and the activity log |

See also [CHANGELOG.md](../CHANGELOG.md).
