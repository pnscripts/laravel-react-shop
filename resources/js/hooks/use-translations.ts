import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';

export type Replacements = Record<string, string | number>;

/**
 * Translate interface text. Keys are the English text, as in Laravel's lang/<locale>.json;
 * a missing translation falls back to the key. Placeholders use Laravel's ":name" style.
 *
 *     const t = useTranslations();
 *     t('Add to cart');
 *     t(':count in stock', { count: product.stock });
 */
export function useTranslations() {
    const { translations } = usePage<SharedData>().props;

    return useCallback(
        (key: string, replacements: Replacements = {}) =>
            Object.entries(replacements).reduce((text, [name, value]) => text.replaceAll(`:${name}`, String(value)), translations?.[key] ?? key),
        [translations],
    );
}
