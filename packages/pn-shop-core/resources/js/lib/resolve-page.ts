import type { ComponentType } from 'react';

const pages = import.meta.glob<{ default: ComponentType }>('../pages/**/*.tsx');

/**
 * Lazily load an Inertia page component by name (e.g. "shop/show").
 */
export async function resolvePage(name: string): Promise<ComponentType> {
    const page = pages[`../pages/${name}.tsx`];

    if (!page) {
        throw new Error(`Page not found: ${name}`);
    }

    return (await page()).default;
}
