import { useTranslations } from '@/hooks/use-translations';
import { Head, Link, router, usePage } from '@inertiajs/react';
import * as React from 'react';
import { type ComponentType, useSyncExternalStore } from 'react';
import * as jsxRuntime from 'react/jsx-runtime';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type AnyComponent = ComponentType<any>;

const blocks = new Map<string, AnyComponent>();
const slots = new Map<string, AnyComponent[]>();
const listeners = new Set<() => void>();
let revision = 0;

function changed(): void {
    revision++;
    listeners.forEach((listener) => listener());
}

/** Render content blocks of this type (PnShop\Cms\Blocks\BlockRegistry key) with the component. */
export function registerBlock(type: string, component: AnyComponent): void {
    blocks.set(type, component);
    changed();
}

/** Add a component to a named slot, e.g. "cart.after_totals". Slots render every component added. */
export function registerSlot(name: string, component: AnyComponent): void {
    slots.set(name, [...(slots.get(name) ?? []), component]);
    changed();
}

export function blockComponent(type: string): AnyComponent | undefined {
    return blocks.get(type);
}

export function slotComponents(name: string): AnyComponent[] {
    return slots.get(name) ?? [];
}

/** Re-render when plugins register blocks or slots (their scripts may load after the page). */
export function useExtensionRegistry(): number {
    return useSyncExternalStore(
        (listener) => {
            listeners.add(listener);

            return () => listeners.delete(listener);
        },
        () => revision,
        () => revision,
    );
}

export type PnShopSdk = {
    version: 1;
    React: typeof React;
    jsx: typeof jsxRuntime;
    inertia: { Head: typeof Head; Link: typeof Link; router: typeof router; usePage: typeof usePage };
    useTranslations: typeof useTranslations;
    registerBlock: typeof registerBlock;
    registerSlot: typeof registerSlot;
};

declare global {
    interface Window {
        PnShop?: PnShopSdk;
    }
}

/**
 * The storefront SDK for plugins: one shared React and Inertia, and the registries.
 * Plugin scripts are prebuilt ES modules that use window.PnShop instead of bundling React.
 */
export function installSdk(): void {
    if (typeof window === 'undefined' || window.PnShop) {
        return;
    }

    window.PnShop = {
        version: 1,
        React,
        jsx: jsxRuntime,
        inertia: { Head, Link, router, usePage },
        useTranslations,
        registerBlock,
        registerSlot,
    };

    window.dispatchEvent(new Event('pnshop:ready'));
}
