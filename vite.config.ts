import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import { coreRoot } from './scripts/vite-theme-overrides';

/**
 * The built-in storefront, from the core package's resources/.
 *
 *     npm run dev / npm run build    public/build, used instead of the core's prebuilt bundle
 *     npm run build:core             the prebuilt bundle the core package ships (theme/dist)
 */
const root = import.meta.dirname;
const core = coreRoot(root);
const shipped = process.env.PNSHOP_CORE_DIST === '1';

export default defineConfig(({ isSsrBuild }) => ({
    // Entries and manifest keys stay "resources/js/app.tsx", wherever the core is installed.
    root: core,
    envDir: root,
    publicDir: false,
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            buildDirectory: shipped ? 'vendor/pnshop/build' : 'build',
            refresh: [resolve(core, 'resources/views/**'), resolve(core, 'routes/**')],
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            'ziggy-js': resolve(root, 'vendor/tightenco/ziggy'),
        },
    },
    build: {
        outDir: isSsrBuild ? resolve(root, 'bootstrap/ssr') : shipped ? resolve(core, 'theme/dist') : resolve(root, 'public/build'),
        emptyOutDir: true,
    },
}));
