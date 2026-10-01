# Themes

A theme decides how the storefront looks and behaves in the browser. The admin (Filament) is not themed. Themes live in `core/Theme` (manager) and `themes/<vendor>/<name>/`.

Staff with **appearance.themes.manage** choose the theme in Admin → Appearance → Themes and adjust its settings under *Customize*.

## What a theme is

```
themes/acme/aurora/
├── pnshop.json            type "theme", parent, settings
├── resources/…            files that replace the storefront's files of the same path
└── dist/                  the prebuilt bundle (Vite build with manifest.json)
```

```json
{
    "id": "acme/aurora",
    "name": "Aurora",
    "version": "1.0.0",
    "type": "theme",
    "parent": "pnshop/default",
    "requires": { "pnshop": ">=0.9" },
    "settings": [
        { "key": "primary", "type": "color", "label": "Brand colour", "default": "#4f46e5",
          "css_var": "--primary", "contrast_var": "--primary-foreground" },
        { "key": "radius", "type": "select", "label": "Corners", "default": "1rem", "css_var": "--radius",
          "options": { "0.375rem": "Slightly rounded", "1rem": "Very rounded" } }
    ]
}
```

- **The default theme:** `pnshop/default` is the storefront that ships in `resources/` (`"builtin": true`).
- **Your own theme:** every other theme ships its own prebuilt bundle in `dist/`. Shops never run Node.
- **Activation:**
  - activating a theme copies `dist/` to `public/themes/<id>/build`;
  - the storefront then loads that bundle;
  - browsers reload fully, because the asset version changes.
- **Fallback:** if the active theme disappears, is not built, or needs another PN Shop version, the storefront falls back to the default theme.

## Settings

- **Where they appear:** the active theme's settings, and its parents', appear in Admin → Settings → *Theme: <name>*.
- **CSS variables:** settings with a `css_var` become CSS custom properties.
  - **Colours** (`type: color`, hex only) apply in light mode. Dark mode keeps the theme's own palette.
  - **`contrast_var`** gets black or white, whichever is more readable on the colour.
  - **Select** values (corners, sizes) apply everywhere, and only the listed option values are allowed.
- **In React:** themes also read their settings through the `theme` prop (`usePage().props.theme.settings`).

## Building a theme (override by path)

A theme only contains the files it changes. Any `themes/<id>/resources/<path>` replaces `resources/<path>` of the storefront; everything else comes from the parent, and in the end from the default theme. Relative imports inside a theme file fall back to the storefront's files.

```bash
npm run build:theme -- acme/aurora      # writes themes/acme/aurora/dist
php artisan pnshop:theme activate acme/aurora
php artisan pnshop:theme list
php artisan pnshop:theme publish acme/aurora   # copy dist/ again after a rebuild
```

- **Same props:** pages and components must keep the props of the files they replace. The page keys and props are the same for every theme: `home`, `shop/index`, `shop/show`, `cart/index`, `checkout/index`, `orders/show`, `cms/page`, `account/*`, `auth/*` and `settings/*`. Their TypeScript types are in `resources/js/types`.
- **Example:** `themes/pnshop/aurora` replaces only the product card and sets its own colour and corners.
- **Server-side rendering:** SSR renders the built-in bundle, so it is switched off while another theme is active.

> Themes run in visitors' browsers with the shop's permissions there. Install only themes you trust.
