import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import { resolvePage } from './lib/resolve-page';
import { pageTitle, setStoreName } from './lib/store-name';

createInertiaApp({
    title: pageTitle,
    resolve: resolvePage,
    setup({ el, App, props }) {
        setStoreName(props.initialPage.props.name);
        router.on('navigate', (event) => setStoreName(event.detail.page.props.name));

        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
