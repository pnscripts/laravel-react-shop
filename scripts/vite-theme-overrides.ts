import { existsSync, readFileSync } from 'node:fs';
import { resolve, sep } from 'node:path';
import type { Plugin } from 'vite';

type ThemeManifest = { id: string; parent?: string; builtin?: boolean };

/**
 * The folders a theme build takes files from, the theme itself first: the theme, then its
 * parents up to (not including) the built-in storefront in resources/.
 */
export function themeChain(root: string, themeId: string): string[] {
    const chain: string[] = [];
    let id: string | undefined = themeId;

    while (id) {
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
 * Override by path: when a module under resources/ is imported, the first theme in the chain
 * that has the same file (themes/<id>/resources/...) wins. Relative imports inside a theme
 * file that the theme does not have fall back to the storefront's own files.
 */
export function themeOverrides(root: string, chain: string[]): Plugin {
    const core = resolve(root, 'resources') + sep;
    const themeResources = chain.map((directory) => resolve(directory, 'resources') + sep);

    const override = (id: string): string | null => {
        const [path, query] = id.split('?');

        if (!path.startsWith(core)) {
            return null;
        }

        for (const themeRoot of themeResources) {
            const candidate = themeRoot + path.slice(core.length);

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
                    resolved = await this.resolve(source, core + importer.slice(themeRoot.length), { ...options, skipSelf: true });
                }
            }

            if (!resolved || resolved.external) {
                return resolved;
            }

            return override(resolved.id) ?? resolved;
        },
    };
}
