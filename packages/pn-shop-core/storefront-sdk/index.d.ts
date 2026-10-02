import type { ComponentType } from 'react';

// eslint-disable-next-line @typescript-eslint/no-explicit-any -- block and slot props are defined by the block or slot.
type AnyComponent = ComponentType<any>;

export type Replacements = Record<string, string | number>;

/** Render CMS blocks of this type (the PHP BlockType key) with the component. */
export function registerBlock(type: string, component: AnyComponent): void;

/**
 * Add a component to a storefront slot. Core slots and their props:
 * - "product.after_price": { product, variant }
 * - "cart.after_totals": { cart }
 * - "checkout.before_submit": { cart, totals }
 * - "order.after_totals": { order }
 * - "footer.top": {}
 */
export function registerSlot(name: string, component: AnyComponent): void;

/** Translate interface text with the shop's translations (lang/*.json, plugins' too). */
export function useTranslations(): (key: string, replacements?: Replacements) => string;
