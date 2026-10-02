# @pnshop/storefront-sdk

Storefront code of a PN Shop plugin is a prebuilt ES module, declared in the plugin's `pnshop.json` as `"storefront": "dist/storefront.js"`. The shop loads it on every storefront page after its own app. The module runs against the storefront's React and Inertia (`window.PnShop`), so there is one React on the page.

Small plugins can skip a build and use `window.PnShop` directly (see `extensions/pnshop/handling-fee/storefront/storefront.js`). With a bundler, alias the shared libraries to this package:

```js
// vite.config.js of your plugin
export default {
    build: {
        lib: { entry: 'src/storefront.tsx', formats: ['es'], fileName: () => 'storefront.js' },
        outDir: 'dist',
    },
    resolve: {
        alias: {
            'react/jsx-runtime': '@pnshop/storefront-sdk/jsx-runtime',
            react: '@pnshop/storefront-sdk/react',
            '@inertiajs/react': '@pnshop/storefront-sdk/inertia',
        },
    },
};
```

```tsx
import { registerBlock, registerSlot, useTranslations } from '@pnshop/storefront-sdk';

registerSlot('product.after_price', ({ variant }) => {
    const t = useTranslations();
    return <p>{t('Free returns within 30 days')}</p>;
});

registerBlock('testimonial', ({ quote, author }) => (
    <blockquote>
        {quote} — {author}
    </blockquote>
));
```

A component that throws is caught and left out, so a plugin error never breaks the page.
