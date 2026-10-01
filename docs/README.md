# PN Shop documentation

Architecture approved 2026-10-01. Phases 0–5 are implemented; the documents below record the plan, research and what has shipped.

| Document | Purpose |
|---|---|
| [architecture/01-discovery-and-gap-analysis.md](architecture/01-discovery-and-gap-analysis.md) | What exists today, what is missing, what to keep, improve or replace |
| [architecture/02-platform-architecture-proposal.md](architecture/02-platform-architecture-proposal.md) | Target architecture, decisions D1–D7, implementation phases (approval checkpoint) |
| [research/platform-comparison.md](research/platform-comparison.md) | OpenCart, PrestaShop, WooCommerce, Shopware, Magento, Bagisto, Lunar: architecture research |
| [research/version-modernization.md](research/version-modernization.md) | Current vs latest stable versions, audits, upgrade plan |
| [research/security-performance-audit.md](research/security-performance-audit.md) | Security and performance findings with file:line and fixes |
| [upgrades/2026-10-phase-0-1.md](upgrades/2026-10-phase-0-1.md) | Upgrade notes for the safety fixes and version upgrade (Phases 0–1) |
| [upgrades/2026-10-phase-2.md](upgrades/2026-10-phase-2.md) | Upgrade notes for the core foundation and admin panel (Phase 2) |
| [upgrades/2026-10-phase-3.md](upgrades/2026-10-phase-3.md) | Upgrade notes for localization and money (Phase 3) |
| [development/localization-and-money.md](development/localization-and-money.md) | Languages, localized URLs, translatable models, interface text, money |
| [upgrades/2026-10-phase-4.md](upgrades/2026-10-phase-4.md) | Upgrade notes for the catalog redesign (Phase 4) |
| [ecommerce/catalog-and-inventory.md](ecommerce/catalog-and-inventory.md) | Products, variants, options, categories, brands, attributes, images, inventory |
| [upgrades/2026-10-phase-5.md](upgrades/2026-10-phase-5.md) | Upgrade notes for customers, database carts, structured checkout and stock reservations (Phase 5) |
| [ecommerce/customers-cart-and-checkout.md](ecommerce/customers-cart-and-checkout.md) | Customer groups, address books, carts, the totals pipeline, checkout, reservations, spam protection |
| [upgrades/2026-10-phase-6.md](upgrades/2026-10-phase-6.md) | Upgrade notes for orders, payments, shipping and tax (Phase 6, in progress) |
| [ecommerce/orders.md](ecommerce/orders.md) | Order numbers, status/payment/fulfillment states, history, OrderWorkflow |
| [development/core-modules.md](development/core-modules.md) | Core module layout, permissions, pipelines and settings for developers |
| [administration/staff-and-roles.md](administration/staff-and-roles.md) | Admin panel, staff accounts, roles and the activity log |

The full `docs/` tree (cms, ecommerce, plugins, themes, api, installation, …) is filled in phase by phase after approval.
