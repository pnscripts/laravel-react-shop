/**
 * The store name comes from the "store.name" setting via the shared `name` prop.
 * Page titles are formatted outside React, so the latest value is kept here.
 */
let storeName: string = import.meta.env.VITE_APP_NAME || 'PN Shop';

export function setStoreName(name: unknown): void {
    if (typeof name === 'string' && name !== '') {
        storeName = name;
    }
}

export function pageTitle(title: string): string {
    return title ? `${title} - ${storeName}` : storeName;
}
