import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import ReactDOMServer from 'react-dom/server';
import { route } from 'ziggy-js';
import { resolvePage } from './lib/resolve-page';
import { pageTitle, setStoreName } from './lib/store-name';
import type { SharedData } from './types';

createServer((page) => {
    setStoreName(page.props.name);

    return createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: pageTitle,
        resolve: resolvePage,
        setup: ({ App, props }) => {
            const { ziggy } = page.props as unknown as SharedData;

            // Ziggy's global route() helper must know the current URL during SSR. Call sites are
            // typed by the global declaration in types/global.d.ts; this wrapper only forwards arguments.
            const config = { ...ziggy, location: new URL(ziggy.location) };
            const forward = route as (...args: unknown[]) => unknown;
            (globalThis as { route?: unknown }).route = (name?: unknown, params?: unknown, absolute?: unknown) =>
                forward(name, params, absolute, config);

            return <App {...props} />;
        },
    });
});
