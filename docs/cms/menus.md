# Menus

Staff edit the storefront's menus in Admin → Content → Menus. The theme decides which menus exist: the default theme has **Header** and **Footer**.

## Items

| Type | Links to |
|---|---|
| Homepage, All products | `/`, `/shop` |
| Page, Category, Brand, Product | the chosen record, at its address in the visitor's language |
| Address (URL) | a shop path (`/shop?brand=acme`), a full `https://` address, `mailto:` or `tel:` |
| Heading without a link | nothing: a column title in the footer, or a dropdown label in the header |

- **Labels:** items take the name of what they link to, or a custom label. Labels can be translated per language.
- **Nesting:** an item can be placed *Inside* a top-level item.
  - In the header, items with children open a dropdown.
  - In the footer, they become columns.
- **Order:** drag rows on the *Items* tab to reorder them.
- **Hidden items:** items whose target is unpublished, hidden or deleted disappear from the storefront by themselves.
- **Empty header:** without header items, the storefront shows the default *Shop* link.

## For developers

- **Building the tree:** `PnShop\Cms\Menus::tree('header')` returns `[{label, url, new_tab, children}]` in the current language. The storefront gets both menus as the shared `menus` prop.
- **Caching:** trees are cached per menu and language. The cache is cleared when a menu, item, page, category, brand or product changes. Entries also expire after ten minutes, so scheduled pages appear on time.
