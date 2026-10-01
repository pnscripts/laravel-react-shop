# Aurora (example child theme)

Extends `pnshop/default`. Every file under `resources/` here replaces the storefront file with the same path; everything else comes from the parent. Aurora replaces only `resources/js/components/product-card.tsx` and sets its own default colour and corners.

Build and activate:

```bash
npm run build:theme -- pnshop/aurora
php artisan pnshop:theme activate pnshop/aurora
```

A theme is distributed with its built `dist/` folder, so shops never need Node.
