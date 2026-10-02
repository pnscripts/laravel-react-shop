import { existsSync, readFileSync, realpathSync } from 'node:fs';
import { resolve, sep } from 'node:path';
import type { Plugin } from 'vite';

type ThemeManifest = { id: string; parent?: string; builtin?: boolean };

/** The built-in storefront theme, which lives in the core package. */
export const DEFAULT_THEME = 'pnshop/default';

/**
 * The pnscripts/pn-shop-core package (its real path, so module ids and manifest keys are
 * stable): vendor/pnscripts/pn-shop-core in a shop, packages/pn-shop-core in the monorepo.
 */
export function coreRoot(root: string): string {
    for (const candidate of [resolve(root, 'vendor/pnscripts/pn-shop-core'), resolve(root, 'packages/pn-shop-core')]) {
        if (existsSync(resolve(candidate, 'resources/js/app.tsx'))) {
            return realpathSync(candidate);
        }
    }

    throw new Error('The PN Shop core package was not found. Run composer install first.');
}

/**
 * The folders a theme build takes files from, the theme itself first: the theme, then its
 * parents up to (not including) the built-in storefront in the core package.
 */
export function themeChain(root: string, themeId: string): string[] {
    const chain: string[] = [];
    let id: string | undefined = themeId;

    while (id && id !== DEFAULT_THEME) {
        if (!/^[a-z0-9-]+\/[a-z0-9-]+$/.test(id)) {
            throw new Error(`Invalid theme id: ${id}`);
        }

        const directory = resolve(root, 'themes', id);
        const manifest = JSON.parse(readFileSync(resolve(directory, 'pnshop.json'), 'utf8')) as ThemeManifest;

        if (manifest.builtin) {
            break;
        }

        if (chain.includes(directory) || chain.length > 10) {
            throw new Error('Themes extend each other in a circle.');
        }

        chain.push(directory);
        id = manifest.parent;
    }

    return chain;
}

/**
 * Override by path: when a module under the core's resources/ is imported, the first theme in
 * the chain that has the same file (themes/<id>/resources/...) wins. Relative imports inside a
 * theme file that the theme does not have fall back to the storefront's own files. Tailwind
 * scans the themes' files too.
 */
export function themeOverrides(core: string, chain: string[]): Plugin {
    const coreResources = resolve(core, 'resources') + sep;
    const stylesheet = resolve(core, 'resources/css/app.css');
    const themeResources = chain.map((directory) => resolve(directory, 'resources') + sep);

    const override = (id: string): string | null => {
        const [path, query] = id.split('?');

        if (!path.startsWith(coreResources)) {
            return null;
        }

        for (const themeRoot of themeResources) {
            const candidate = themeRoot + path.slice(coreResources.length);

            if (existsSync(candidate)) {
                return query ? `${candidate}?${query}` : candidate;
            }
        }

        return null;
    };

    return {
        name: 'pnshop-theme-overrides',
        enforce: 'pre',
        async resolveId(source, importer, options) {
            if (source.startsWith('\0')) {
                return null;
            }

            let resolved = await this.resolve(source, importer, { ...options, skipSelf: true });

            // A relative import in a theme file that only exists in the storefront (or a parent).
            if (!resolved && importer) {
                const themeRoot = themeResources.find((candidate) => importer.startsWith(candidate));

                if (themeRoot) {
                    resolved = await this.resolve(source, coreResources + importer.slice(themeRoot.length), { ...options, skipSelf: true });
                }
            }

            if (!resolved || resolved.external) {
                return resolved;
            }

            return override(resolved.id) ?? resolved;
        },
        transform(code, id) {
            if (id.split('?')[0] !== stylesheet) {
                return null;
            }

            return code + themeResources.map((directory) => `\n@source ${JSON.stringify(directory)};`).join('');
        },
    };
}
