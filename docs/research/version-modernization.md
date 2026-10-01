# PN Shop (laravel-react-shop) — Version Modernization Analysis

Date: 2026-10-01. Repo: `DEV/Projects/pnscripts/products/laravel-react-shop` (branch `main` only; no `develop` branch exists locally or on origin).
Method: read-only. `composer show/outdated/audit`, `npm ls/outdated/audit`, Packagist p2 API, `npm view`, GitHub raw/API for `laravel/react-starter-kit@main`, official docs/blogs. Nothing in the repo was modified; `.env` not read.

Legend: **[V]** verified from registry/official source today; **[U]** unverified / inferred.

---

## 0. Local toolchain

| Tool | Local | Note |
|---|---|---|
| PHP | 8.4.25 (CLI, NTS) | Latest PHP is **8.5.11** (2026-09-24) [V php.net releases JSON] |
| Node | v22.23.2 | Node 22 = Maintenance LTS until 2027-04-30; **Node 24 "Krypton" is Active LTS (v24.21.0)**, moves to maintenance 2026-10-20; **Node 26** current (v26.10.0), becomes LTS 2026-10-28 [V nodejs/Release schedule.json, nodejs.org/dist/index.json] |
| npm | 10.9.8 | |
| Composer | 2.8.6 (2025-02-25) | old; `composer self-update` recommended (not run) |

---

## 1. Direct dependencies — constraint vs installed vs latest

### Composer (`composer show --direct` / `composer outdated --direct` / Packagist)

| Package | Constraint | Locked | Latest stable | Gap |
|---|---|---|---|---|
| php | ^8.3 | (8.4 local/CI) | 8.5.11 | — |
| laravel/framework | ^13.0 | 13.29.0 | **13.34.0** (2026-09-29) | patch; **13.29 has advisory** (see audit) |
| inertiajs/inertia-laravel | ^2.0 | 2.0.25 | **3.4.0** (v2 line: 2.0.28) | major |
| tightenco/ziggy | ^2.4 | 2.6.4 | 2.6.4 | current, but starter kit replaced it with Wayfinder |
| laravel/telescope | ^5.7 | 5.22.1 | 5.25.0 | minor |
| dedoc/scramble | ^0.13 | 0.13.42 | 0.13.46 | patch — **unused (no routes/api.php)** |
| laravel/tinker | ^3.0 | 3.0.2 | 3.0.2 | current |
| barryvdh/laravel-debugbar (dev) | ^4.0 | 4.4.2 | 4.4.4 | patch |
| fakerphp/faker (dev) | ^1.23 | 1.24.1 | 1.24.1 | current |
| laravel/pail (dev) | ^1.2.2 | 1.2.7 | 1.2.7 | current |
| laravel/pint (dev) | ^1.18 | 1.30.5 | 1.32.1 (php ^8.3) | minor |
| laravel/sail (dev) | ^1.41 | 1.67.0 | 1.68.0 | minor |
| mockery/mockery (dev) | ^1.6 | 1.6.15 | 1.6.15 | current |
| nunomaduro/collision (dev) | ^8.6 | 8.9.5 | 8.9.5 | current |
| phpunit/phpunit (dev) | ^12.5 | 12.5.34 | **13.3.6** (requires PHP >= 8.4.1) | major |

### npm (`npm ls --depth=0` / `npm outdated`)

| Package | Constraint | Installed | Wanted | Latest | Gap |
|---|---|---|---|---|---|
| react / react-dom | ^19.0.0 | 19.0.0 | 19.3.0 | 19.3.0 (2026-09-09) | minor x3 |
| @types/react / -dom | ^19.0.x | 19.0.10 / 19.0.4 | 19.3.0 | 19.3.0 | minor |
| @inertiajs/react | ^2.0.0 | 2.0.4 | 2.3.28 | **3.7.1** | major |
| typescript | ^5.7.2 | 5.8.2 | 5.9.3 | **7.0.2** (npm `latest`); 6.0.3 also exists | 2 majors |
| vite | ^6.0 | 6.2.0 | 6.4.3 | **8.3.1** | 2 majors; **6.2.0 vulnerable** |
| @vitejs/plugin-react | ^4.3.4 | 4.3.4 | 4.7.0 | **6.1.1** (peer vite ^8) | 2 majors |
| laravel-vite-plugin | ^1.0 | 1.2.0 | 1.3.0 | **3.2.0** (peer vite ^8) | 2 majors |
| tailwindcss / @tailwindcss/vite | ^4.0.x | 4.0.10 | 4.3.3 | 4.3.3 | minor |
| @tailwindcss/oxide-linux-x64-gnu (optional) | ^4.0.1 | 4.0.10 | 4.3.3 | 4.3.3 | — |
| lightningcss-linux-x64-gnu (optional) | ^1.29.1 | 1.29.1 | 1.33.0 | 1.33.0 | — |
| @rollup/rollup-linux-x64-gnu (optional) | **4.9.5 (pinned exact)** | 4.9.5 | 4.9.5 | 4.63.6 | stale pin; remove (Vite 8 uses Rolldown) |
| tailwind-merge | ^3.0.1 | 3.0.2 | 3.7.0 | 3.7.0 | minor |
| tailwindcss-animate | ^1.0.7 | 1.0.7 | — | — | deprecated in shadcn for TW4; starter uses **tw-animate-css 1.4.0** |
| class-variance-authority | ^0.7.1 | 0.7.1 | | 0.7.1 | current |
| clsx | ^2.1.1 | 2.1.1 | | 2.1.1 | current |
| lucide-react | ^0.475.0 | 0.475.0 | 0.475.0 | **1.49.0** (1.0 released 2026-03-23) | caret on 0.x pins minor; major |
| @headlessui/react | ^2.2.0 | 2.2.0 | 2.2.10 | 2.2.10 | patch (starter kit no longer uses it) |
| @radix-ui/react-* (14 pkgs) | ^1.x/^2.x | 1.1.x–2.1.6 | latest in-range | e.g. dialog 1.1.23, select 2.3.7, slot 1.3.3 | all in-range; unified `radix-ui` 1.6.7 available |
| concurrently | ^9.0.1 | 9.1.2 | 9.2.4 | 10.0.5 (Node >= 22) | major (only used via npx in composer dev) |
| globals | ^15.14.0 | 15.15.0 | 15.15.0 | 17.13.0 | major |
| eslint / @eslint/js | ^9.17 / ^9.19 | 9.21.0 | 9.39.5 | **10.11.0 / 10.0.1** | major |
| typescript-eslint | ^8.23.0 | 8.26.0 | 8.71.0 | 8.71.0 (peer **typescript <6.1.0**) | minor |
| eslint-plugin-react | ^7.37.3 | 7.37.4 | 7.37.5 | 7.37.5 | patch |
| eslint-plugin-react-hooks | ^5.1.0 | 5.2.0 | 5.2.0 | **7.1.1** | major (React Compiler rules) |
| eslint-config-prettier | ^10.0.1 | 10.0.2 | 10.1.8 | 10.1.8 | minor |
| prettier | ^3.4.2 | 3.5.3 | 3.9.9 | 3.9.9 | minor |
| prettier-plugin-organize-imports | ^4.1.0 | 4.1.0 | 4.3.0 | 4.3.0 | minor |
| prettier-plugin-tailwindcss | ^0.6.11 | 0.6.11 | 0.6.14 | 0.8.1 (Node >= 20.19) | 0.x major |
| @types/node | ^22.13.5 | 22.13.9 | 22.20.4 | 26.6.3 | keep in line with runtime (22 or 24) |

Notes: dependencies/devDependencies split is starter-kit style (build deps in `dependencies`); harmless for an app.

---

## 2. Security audits

### `composer audit` — 3 advisories, 2 packages
| Package | Installed | Severity | Advisory | Fixed in |
|---|---|---|---|---|
| laravel/framework | 13.29.0 | low | CVE-2026-102279 / GHSA-jh5r-qr3c-85q8 "XSS in Debug Page Information" (affects >=13.0.0 <13.30.0) | >= 13.30.0 |
| league/commonmark (transitive) | 2.10.0 | **high** | GHSA-3q6v-r5mr-hxv8 quadratic DoS in GFM table extension (<= 2.10.1) | 2.10.2+ (2.10.3 latest) |
| league/commonmark | 2.10.0 | medium | GHSA-97jj-33gv-5xf9 DisallowedRawHtml bypass (<= 2.10.1) | 2.10.2+ |

Fix: `composer update laravel/framework league/commonmark --with-dependencies` (minor/patch only).

### `npm audit --omit=dev` — 14 vulnerabilities: 1 low, 3 moderate, 8 high, 2 critical
(Because build tooling sits in `dependencies`, "prod" includes vite/rollup.)
| Package | Sev | Direct? | Range |
|---|---|---|---|
| form-data | **critical** | no | 4.0.0–4.0.5 |
| shell-quote | **critical** | no (via concurrently) | <= 1.8.4 |
| vite | high | **yes** | <= 6.4.2 (10+ fs.deny bypass / path traversal / WS file-read advisories) |
| rollup | high | no | 4.0.0–4.58.0 (arbitrary file write) |
| axios | high | no (via @inertiajs/core v2) | 1.0.0–1.19.0 |
| browserslist, lodash, nanoid, picomatch, postcss | high | no | various |
| @babel/helpers, follow-redirects, qs | moderate | no | |
| @babel/core | low | no | |

### `npm audit` (all) — 22 vulnerabilities: 3 low, 5 moderate, 12 high, 2 critical
Adds dev-only: eslint (low, direct, 9.10–9.26), @eslint/plugin-kit (low), @humanfs/node, ajv (moderate), brace-expansion, flatted, js-yaml, minimatch (high).
All report `fixAvailable: true` — i.e. **`npm update` within existing ranges (vite 6.4.3, eslint 9.39.x, etc.) clears all of them**; no major bump needed for security. Real-world exposure is mostly dev-server (Vite fs.deny) — still fix first.

---

## 3. Latest stable versions (as of 2026-10-01) and breaking changes

| Tech | Latest stable | Requirements | Breaking vs repo lock | Source |
|---|---|---|---|---|
| **PHP** | 8.5.11 | — | 8.5: pipe operator, deprecations (backtick operator, non-canonical casts…) [U detail] | php.net/releases JSON [V] |
| **Laravel** | 13.34.0 | PHP 8.3–8.5 | none (same major). L13 bug fixes until Q3 2027, security until 2028-03-17. L14 expected ~Q1 2027 [U] | laravel.com/docs/13.x/releases [V] |
| **inertia-laravel** | 3.4.0 | PHP ^8.2, Laravel ^11.35/12/13 | see Inertia 3 below | Packagist [V] |
| **@inertiajs/react** | 3.7.1 (3.0.0 on 2026-03-25) | React ^19 | **Inertia 3 stable since Mar 2026.** Axios removed (built-in XHR), `qs`/`lodash-es` no longer bundled; `LazyProp`/`Inertia::lazy` → `Inertia::optional()`; events `invalid`→`httpException`, `exception`→`networkError`; `router.cancel()`→`router.cancelAll()`; `hideProgress/revealProgress` removed; config restructured (`pages` key); Blade `<title inertia>` → `data-inertia`; initial page via `<script type="application/json">`; arrow-fn layouts must be arrays; `useForm` processing resets in `onFinish`; ESM-only, ES2022 target. New `@inertiajs/vite` plugin auto-generates resolve/setup/SSR; new `layout:` resolver and `withApp` in `createInertiaApp`. | inertiajs.com/docs/v3/getting-started/upgrade-guide [V] |
| **React** | 19.3.0 (2026-09-09) | — | minor; no breaking expected from 19.0 [U changelog not read in detail] | npm [V] |
| **TypeScript** | **7.0.2** (7.0 announced 2026-07-08; Go-native; 8–12x faster); 6.0.3 last JS-based | — | TS7 **ships no programmatic API** (expected 7.1) → typescript-eslint 8.71 peer is `typescript <6.1.0`. Removed: `target es5`, `baseUrl`, `moduleResolution node/node10`, amd/umd/system. Use `typescript@npm:@typescript/typescript6` side-by-side for linting. Repo `tsconfig.json` **does use `"baseUrl": "."`** (line 110, with `paths`) → must be removed/`paths` made root-relative before TS 7 [V]; `moduleResolution: bundler` is fine. | devblogs.microsoft.com/typescript/announcing-typescript-7-0/ [V]; npm dist-tags [V] |
| **Tailwind CSS** | 4.3.3 (4.1 Apr-2025, 4.2 Feb-2026, 4.3 May-2026) | — | minor within v4 | npm [V] |
| **Vite** | 8.3.1 (8.0 on 2026-03-12) | Node ^20.19 / >=22.12 | v7: Node 20.19+, default browser target baseline-widely-available. v8: **Rolldown + Oxc** replace esbuild/Rollup; `build.rollupOptions`→`rolldownOptions`; `esbuild:` config option replaced by `oxc` (repo `vite.config.ts` sets `esbuild.jsx`) [U exact mapping]; CJS interop changes; object `manualChunks` removed. | vite.dev/guide/migration [V] |
| @vitejs/plugin-react | 6.1.1 | vite ^8, Node 20.19+ | Babel no longer bundled; React Compiler via `@rolldown/plugin-babel` + `reactCompilerPreset` | npm peer deps [V], starter kit vite.config [V] |
| laravel-vite-plugin | 3.2.0 | vite ^8 | `laravel-vite-plugin/inertia-helpers` (`resolvePageComponent`) superseded by `@inertiajs/vite` [U whether removed]; adds `fonts` (bunny) helper | npm [V], starter kit [V] |
| **Vite+ (`vite-plus`)** | **1.0.0 (2026-09-28)**; starter kit pins 0.3.0 | Node ^22.18 / ^24.11 / >=26 | Single `vp` CLI wrapping Vite, Rolldown, Vitest, Oxlint, Oxfmt; replaces ESLint+Prettier in the starter kits (Aug 2026) | npm [V], laravel-news.com/laravel-starter-kits-vite-plus [V] |
| **Node LTS** | 24.21.0 (Active LTS → maint 2026-10-20); 26 LTS from 2026-10-28 | — | Node 22 ok until 2027-04-30 | nodejs/Release [V] |
| **Ziggy vs Wayfinder** | ziggy 2.6.4; **laravel/wayfinder 0.1.21** (still 0.x/beta), `@laravel/vite-plugin-wayfinder` 0.1.10 | PHP ^8.2, L11–13 | Starter kit uses **Wayfinder** (typed TS functions per controller action/named route, generated into `resources/js/{actions,routes,wayfinder}`). Repo uses Ziggy `route()` in **28 TS files** + `@routes` Blade + SSR global hack. | Packagist [V], starter kit composer.json [V] |
| laravel/telescope | 5.25.0 | PHP ^8.0 | none. Repo loads it in **prod `require`** and registers provider unconditionally — move to dev or guard. | Packagist [V] |
| dedoc/scramble | 0.13.46 (still 0.x) | PHP ^8.1 | none; **no API routes exist → candidate for removal** | Packagist [V] |
| PHPUnit | 13.3.6 | **PHP >= 8.4.1** | major: removals of deprecated APIs [U detail] | Packagist [V] |
| Pest | 5.2.1 (PHP ^8.4, PHPUnit ^13.3.4); Pest 4.7.8 (PHP ^8.3, PHPUnit ^12.5.33) | | Starter kit React variant still uses PHPUnit 12.5 | Packagist [V] |
| Larastan | 3.12.2 (phpstan ^2.2.14; Laravel ^11.44.2/^12.4.1/^13) | PHP ^8.2 | new; starter kit uses Larastan ^3.9 at **level 7** | Packagist [V], starter `phpstan.neon` [V] |
| PHPStan | 2.2.16 | | | Packagist [V] |
| laravel/pint | 1.32.1 | PHP ^8.3 | starter uses `pint --parallel`, ships `pint.json` | Packagist [V] |
| ESLint | 10.11.0 | Node ^20.19 / ^22.13 / >=24 | eslintrc fully removed (repo already flat config ✓), `/* eslint-env */` errors, LegacyESLint removed | eslint.org/blog/2026/02/eslint-v10.0.0-released/ [V] |
| eslint-plugin-react-hooks | 7.1.1 | | v6/v7 add React-Compiler-powered rules in `recommended` | npm [V]; [U rule list] |
| Prettier | 3.9.9 | | minor | npm [V] |
| shadcn CLI / Radix | shadcn 4.21.0; `radix-ui` umbrella 1.6.7; individual `@radix-ui/react-*` still maintained | React 16.8–19 | shadcn now generates TW4 components with `tw-animate-css` and `radix-ui` imports [U] | npm [V] |
| lucide-react | 1.49.0 | React 16.5–19 | 1.0 (2026-03-23): icon renames / brand icons removal [U — check release notes before bump] | npm [V] |

### Candidate new dependencies
| Package | Latest | PHP / Laravel | Comment |
|---|---|---|---|
| laravel/sanctum | 4.3.3 | ^8.2 / 11–13 | only if a token API/mobile/SPA-on-other-domain is planned; not needed for Inertia session auth |
| spatie/laravel-permission | 8.3.0 | **^8.3** / ^12–13 | replaces `is_admin` boolean with roles/permissions; also integrates with Filament Shield |
| spatie/laravel-medialibrary | 11.23.8 | ^8.2 / 10.2–13 | product images, conversions, responsive images |
| intervention/image | 4.3.3 | ^8.3 | lower-level alternative if only resize is needed |
| laravel/scout | 11.8.0 | ^8.0 / 9–13 | catalog search (database driver first, Meilisearch/Typesense later) |
| laravel/fortify | 1.40.0 | ^8.2 / 11–13 | starter kit auth backend (2FA, passkeys via `@laravel/passkeys` 0.4.0) |
| filament/filament | 5.9.0 | ^8.2 / 11.28+ ; Livewire ^4.4.2 | see §7 |
| laravel/boost | 2.10.1 | ^8.2 / 13 | AI-agent MCP/guidelines; starter kit ignores its files |
| rector/rector + driftingly/rector-laravel | 2.6.7 / 2.6.2 | | optional automated refactors |

---

## 4. Repo vs current `laravel/react-starter-kit@main`

Source: raw.githubusercontent.com/laravel/react-starter-kit/main/{composer.json,package.json,vite.config.ts,phpstan.neon,.github/workflows/tests.yml,resources/js/app.tsx} and GitHub tree/commit API [V]. Last commits 2026-09-21 ("Use oxfmt's stylesheet option…", "Migrate all starter kits to Vite+" 2026-08-28).

| Area | This repo (snapshot ≈ early 2025 kit) | Starter kit main (2026-09) |
|---|---|---|
| Inertia | v2 (`inertia-laravel ^2`, `@inertiajs/react ^2`) | **v3** + `@inertiajs/vite` plugin; `app.tsx` has no `resolve`/`setup`, uses `layout:` resolver, `strictMode`, `withApp` (Tooltip + Sonner toaster); no separate `ssr.tsx` needed |
| Routing in TS | Ziggy (`@routes`, `route()`, `ziggy-js` alias in vite.config) | **Wayfinder** (`laravel/wayfinder`, `@laravel/vite-plugin-wayfinder`, `formVariants: true`) |
| Auth | hand-rolled `Auth/*Controller` (8 controllers, Breeze style) + `routes/auth.php` | **Fortify** (`app/Actions/Fortify`, `FortifyServiceProvider`, `config/fortify.php`), **2FA** (two-factor columns migration, `manage-two-factor`, recovery codes), **passkeys** (`@laravel/passkeys`, passkeys table), `settings/security` page replaces password page; `InstallFeaturesCommand` + `laravel/chisel` to toggle features |
| Frontend tooling | ESLint 9 + Prettier 3 (+ organize-imports, tailwind plugins), `npm run lint` = `eslint . --fix` | **Vite+** (`vp build/dev/check`), Oxlint (type-aware, `denyWarnings`) + Oxfmt (Tailwind sort) configured inside `vite.config.ts`; no eslint/prettier config files; pnpm workspace file present |
| React | 19.0, no compiler | ^19.2 + **React Compiler** (`babel-plugin-react-compiler` via `@rolldown/plugin-babel`) |
| Build | Vite 6, plugin-react 4, laravel-vite-plugin 1 | Vite 8, plugin-react 6, laravel-vite-plugin 3 (+ bunny fonts) |
| UI deps | headlessui, tailwindcss-animate | no headlessui; `tw-animate-css`, `sonner`, `input-otp` |
| TS layout | `types/index.d.ts`, `global.d.ts` | `types/{auth,navigation,ui,index}.ts` |
| Static analysis | none | **Larastan level 7** (`phpstan.neon`: app, bootstrap/app.php, config, database, routes) |
| Composer scripts | `dev`, `dev:ssr` | `setup`, `dev`, `lint`, `lint:check`, `types:check` (phpstan), `test` (config:clear + pint --test + phpstan + artisan test), `ci:check` (npm check + tsc + test) |
| CI | `lint.yml` (mutating, never fails) + `tests.yml`; branches `develop`,`main`; `contents: write`; Node 22, PHP 8.4, xdebug coverage | single `tests.yml`: push `main` + all PRs, `permissions: contents: read`, actions **pinned by SHA**, `persist-credentials: false`, PHP 8.3, Node 22, `composer setup` then `composer ci:check`; Dependabot for github-actions with 5-day cooldown |
| Dev extras | Telescope, Debugbar, Scramble | none of these; `laravel/pao` dev [U purpose] |

**Recommendation: selective re-alignment, not a re-base.** The shop has diverged materially (storefront layout, cart/checkout/admin, 28 Ziggy call sites, custom auth controllers). Adopt the starter kit's *platform* layers (Inertia 3 + `@inertiajs/vite`, Vite 8, Larastan 7, composer script contract, CI shape, Wayfinder) and treat *feature* layers (Fortify/2FA/passkeys, Vite+/Oxc tooling) as separate, optional decisions. Fortify migration is worth it if 2FA/passkeys are product goals for the shop's admin; otherwise defer.

---

## 5. Recommended target version matrix

| Component | Now | Target (phase) | Rationale |
|---|---|---|---|
| PHP (constraint / CI) | ^8.3 / 8.4 | **^8.4** / CI matrix 8.4 + 8.5 (P3) | PHPUnit 13 / Pest 5 need 8.4; L13 supports 8.5 |
| laravel/framework | 13.29.0 | ^13.34 (P1) | security fix |
| league/commonmark | 2.10.0 | >= 2.10.3 (P1) | high-sev DoS |
| inertia-laravel / @inertiajs/react | 2.0.25 / 2.0.4 | 2.0.28 / 2.3.x (P1) → **^3.4 / ^3.7 + @inertiajs/vite** (P4) | v3 stable 6 months, starter kit on it |
| Ziggy → Wayfinder | ziggy 2.6.4 | keep Ziggy through P4; **Wayfinder ^0.1.21** (P6, optional) | Wayfinder still 0.x |
| telescope | 5.22.1 (prod) | ^5.25, move to require-dev or env-guard (P1/P2) | |
| scramble | 0.13.42 | remove (P2) unless an API is planned | unused |
| phpunit | 12.5.34 | ^12.5 now; **13.3** after PHP floor 8.4 (P3) | or Pest 4/5 if desired — not required |
| larastan | — | **^3.12, level 5 → 7** (P2) | match starter kit level 7 |
| pint | 1.30.5 | ^1.32 + `pint.json`, `--test` in CI (P2) | |
| react / react-dom / @types | 19.0 | **^19.3** (P1) | |
| typescript | 5.8.2 | **^5.9.3** (P1); 6.0.x optional; **defer 7.x** until typescript-eslint/Oxlint type-aware support is settled (P7) | TS7 has no API yet |
| vite | 6.2.0 | 6.4.3 (P1 security) → **^8.3** (P5) | |
| @vitejs/plugin-react | 4.3.4 | ^6.1 (P5) | |
| laravel-vite-plugin | 1.2.0 | ^3.2 (P5) | |
| tailwindcss + @tailwindcss/vite | 4.0.10 | ^4.3.3 (P1) | |
| tailwindcss-animate | 1.0.7 | tw-animate-css ^1.4 (P5) | TW4-native |
| radix | individual 1.x | latest in range (P1) | |
| lucide-react | 0.475 | ^1.49 (P5, verify renamed icons) | |
| eslint / @eslint/js / ts-eslint / react-hooks | 9.21 / 5.2 | 9.39 (P1) → ESLint 10 + react-hooks 7 (P5) **or** Vite+ (Oxlint/Oxfmt) (P7) | |
| prettier (+plugins) | 3.5.3 | ^3.9.9, tailwind plugin 0.6.14 (P1) | |
| Node (CI / .nvmrc) | 22 | **24 LTS** (P2), add 26 after 2026-10-28 | |
| Composer CLI | 2.8.6 | latest 2.x | |
| Candidates | — | spatie/laravel-permission ^8.3, medialibrary ^11.23, scout ^11.8 (feature work, after P5) | |

---

## 6. Ordered low-risk upgrade plan

Each phase = one PR on a feature branch, merged to `main`. After every phase run: `composer test` (pint --test, phpstan, phpunit), `npm run types`, `npm run lint` (check mode), `npm run build` and `npm run build:ssr`, smoke test: `/`, `/shop`, product page, cart add/update/remove, checkout (guest + auth), `/orders/{order}`, admin products CRUD + orders status, login/register/password reset, settings.

**P0 — CI first (no dependency changes).** Fix workflows so later phases are actually gated:
- Triggers: `push: branches: [main]` + `pull_request:` (all branches); drop `develop`.
- `permissions: contents: read` (lint.yml currently `contents: write`).
- Make lint non-mutating and failing: `vendor/bin/pint --test`, `npm run format:check`, `eslint .` (add `lint:check` script without `--fix`), `npm run types` (tsc) — currently not run in CI at all.
- Use `npm ci` (not `npm install`), `cache: npm`, `coverage: none` unless coverage is uploaded.
- Pin actions (checkout v4 is outdated; starter uses SHA-pinned checkout v7 / setup-node v7) and add Dependabot (github-actions + composer + npm, grouped, cooldown).
- Add `composer audit` and `npm audit --audit-level=high --omit=dev` steps (non-blocking initially, blocking after P1).
- Optionally collapse into one `ci.yml` and add composer scripts `lint:check`, `types:check`, `test`, `ci:check` mirroring the starter kit.
Verify: workflow fails on an intentional Pint/ESLint/TS violation in a throwaway PR.

**P1 — Security + in-range updates (lowest risk).** `composer update` limited to: laravel/framework, league/commonmark, telescope, pint, sail, debugbar, scramble (or remove), inertia-laravel 2.0.28. `npm update` within ranges (vite 6.4.3, react 19.3, @inertiajs/react 2.3.x, tailwind 4.3.3, radix, eslint 9.39, ts 5.9.3, prettier 3.9). Remove the exact pin `@rollup/rollup-linux-x64-gnu@4.9.5` from optionalDependencies (stale; npm handles native binaries). Verify `composer audit` and `npm audit` are clean; Tailwind 4.0→4.3 visual diff check.

**P2 — Static analysis & hygiene.** Add `larastan/larastan ^3.12`, `phpstan.neon` (paths as starter kit), start at **level 5** with a baseline only if needed, raise to **level 7** (starter-kit parity) within the same or next PR; add `pint.json`. Move Telescope to `require-dev` with conditional registration (or keep but gate via `TELESCOPE_ENABLED`); drop Scramble. Add `.nvmrc`/`engines` = Node 24; CI Node 24. Verify phpstan green in CI.

**P3 — PHP floor 8.4 + PHPUnit 13.** `php: ^8.4`, CI matrix 8.4/8.5, PHPUnit ^13.3 (check removed deprecated assertions/attributes). Optional: Pest 5 conversion — not required; defer.

**P4 — Inertia 3 (medium risk).** `inertia-laravel ^3.4`, `@inertiajs/react ^3.7`, add `@inertiajs/vite`. Republish/merge `config/inertia.php` (`pages` key), Blade `<title inertia>`→`data-inertia`, replace `Inertia::lazy`/LazyProp if any (none found by grep), event names, arrow-function layouts → arrays, `useForm` onFinish semantics; if any code imports `axios` (none in `resources/js`) install it explicitly. Simplify `app.tsx`/`ssr.tsx` per v3 docs (keep Ziggy SSR global for now). Verify every form (processing spinners), flash messages, partial reloads, SSR build, `tests/Feature` Inertia assertions (`assertInertia`).
Note: Inertia 3 + Vite 6 is fine; v3 is ESM-only/ES2022.

**P5 — Vite 8 toolchain (medium risk).** vite ^8.3, @vitejs/plugin-react ^6.1, laravel-vite-plugin ^3.2, replace `esbuild: { jsx }` block (plugin-react handles JSX), replace `resolvePageComponent` with `@inertiajs/vite` page resolution, tw-animate-css, lucide-react 1.x, ESLint 10 + react-hooks 7 + globals 17, prettier-plugin-tailwindcss 0.8. Optional: React Compiler via `@rolldown/plugin-babel` (starter kit pattern) — enable in a separate PR. Verify build output, SSR, HMR, bundle size, icons.

**P6 — Wayfinder (optional, medium effort).** Add `laravel/wayfinder` + vite plugin, migrate 28 Ziggy call sites incrementally (both can coexist), then remove Ziggy, `@routes`, `ziggy-js` alias and the SSR `global.route` hack. Wayfinder is still 0.x — accept churn or defer.

**P7 — Defer / evaluate.**
- **TypeScript 7**: defer until typescript-eslint supports it (TS 7.1 API) or tooling moves to Oxlint; could use TS7 `tsc` for `types` only with `@typescript/typescript6` alias for ESLint.
- **Vite+ (Oxlint/Oxfmt)**: 1.0 released 2026-09-28; starter kit moved to it. Evaluate after P5 via `vp migrate`; replaces ESLint/Prettier.
- **Fortify + 2FA + passkeys**: product decision; would replace 8 custom auth controllers.
- **Laravel 14**: expected ~Q1 2027 [U].
- New feature deps (permission, medialibrary, scout) after platform is current.

---

## 7. Filament (admin panel option)

- **Latest stable: Filament v5.9.0** (2026-09-26); v5.0.0 released 2026-01-16. v4 is still maintained (v4.14.0 same day) but **v4 will not support Livewire 4**. [V Packagist; filamentphp.com/insights/danharrin-filament-v5-blueprint]
- **Requirements v5:** PHP 8.2+, Laravel 11.28+ (`illuminate/contracts ^11.28|^12|^13`), **Livewire ^4.4.2** (latest 4.4.7), Tailwind 4.1+ (only for custom themes), `ext-intl`. Compatible with this repo's Laravel 13 / PHP 8.4. v4→v5 upgrade is mostly automated (upgrade script) — v5 is essentially "v4 on Livewire 4". [V filamentphp.com/docs/5.x/upgrade-guide, installation]
- **How panels/plugins register things:** a `PanelProvider` configures a panel (`->discoverResources()`, `->resources([...])`, `->pages([...])`, `->widgets([...])`, `->navigationGroups()`, `->navigationItems()`, `->plugins([...])`, render hooks, middleware/authMiddleware). A plugin implements `Filament\Contracts\Plugin` with `getId()`, `register(Panel $panel)` (can call any panel config: resources, pages, widgets, themes, render hooks) and `boot(Panel $panel)` (runs only when panel is in use). Navigation is derived from resources/pages (`$navigationGroup`, `$navigationSort`, `$navigationIcon`, `shouldRegisterNavigation()`) plus explicit `NavigationItem`s. [V filamentphp.com/docs/5.x/plugins/panel-plugins]
- **Permissions:** User implements `FilamentUser::canAccessPanel(Panel)` (mandatory in production). Resources auto-check Laravel **model policies** (`viewAny/create/update/view/delete/...`); custom actions must be authorized manually. Role/permission UI typically via spatie/laravel-permission + **Filament Shield** (generates policies/permissions per resource/page/widget). [V filamentphp.com/docs/5.x/advanced/security]
- **No Node build on production?** Yes for standard panels: Filament ships **precompiled CSS/JS**; assets (core + plugins registered via `FilamentAsset::register([Css::make(), Js::make(), AlpineComponent::make()])`) are copied to `public/` by `php artisan filament:assets` (run on deploy; wired into composer `post-autoload-dump` by the installer). Node/Vite is needed only for a **custom theme** (Tailwind 4 build) or for plugin JS that must be bundled — and those can be built in CI and shipped as artifacts. [V filamentphp.com/docs/5.x/advanced/assets]
- **Fit for PN Shop:** Filament is Livewire/Blade, not React/Inertia — it would run as a separate `/admin` panel beside the Inertia storefront (shared auth/session works). Trade-off: two UI stacks; gains: mature CRUD, tables, filters, policies, plugin ecosystem, zero-Node-build plugin distribution. If "plugins installable without a build step" is a product goal, Filament solves it natively for the admin side.

## 8. Shipping prebuilt React plugin bundles that share the host's React/Inertia

Constraint: React hooks and Inertia's `PageContext`/router are module singletons — a plugin that bundles its own `react` or `@inertiajs/react` causes "Invalid hook call" or a dead `usePage()`/`router`. Plugins must resolve `react`, `react-dom`, `react/jsx-runtime`, `@inertiajs/react` (and ideally UI kit/`@/components/ui`) **to the host's instances at runtime**.

Options (feasibility, in recommended order):
1. **Import map + Vite library mode with externals (ESM)** — plugin: `build.lib { formats:['es'] }`, `rolldownOptions.external: ['react','react-dom','react/jsx-runtime','@inertiajs/react', ...]` (Vite 8 naming). Host: exposes its singletons once (`window.__PN__ = { React, ReactDOM, jsxRuntime, Inertia }`) and serves tiny **shim ES modules** (`export default window.__PN__.React; export const { useState, ... } = window.__PN__.React;`) mapped via a `<script type="importmap">` emitted in `app.blade.php` before the Vite entry. Native import maps are baseline in all evergreen browsers. Plugin manifest (PHP side) lists its entry URL + page names. Feasible and lightweight; requires keeping one agreed React major across host and plugins (peer-version check in the plugin manifest). Shim export lists must be maintained (or generated).
2. **IIFE/UMD globals** — `output.format: 'iife'`, `globals: { react: 'React', ... }`. Simplest, no import map, but no code-splitting and Vite 8 no longer polyfills `import.meta.url` in IIFE/UMD [V vite.dev/guide/migration]. Fine for small plugins.
3. **Module Federation (`@module-federation/vite`)** — supports Vite 5–8, real shared-scope negotiation with `singleton: true` for react/react-dom/@inertiajs/react. Most robust versioning, heaviest setup; host and remotes must all use the MF plugin. [V npm / module-federation.io; community sources]
- **Inertia page resolver:** v3 `createInertiaApp({ resolve })` accepts a function returning a component or a Promise of a module, so the host can route plugin pages: `resolve: name => name.startsWith('plugin:') ? import(/* @vite-ignore */ pluginEntryUrl(name)).then(m => m.pages[pageName]) : localPages[name]()`. When the `@inertiajs/vite` plugin auto-generates the resolver, a custom `resolve` (or `pages` with `transform`) is needed to add the plugin branch [U exact combination with the plugin — test]. Server side, the plugin's Laravel service provider registers routes that `Inertia::render('plugin:vendor/name/Page')`. SSR: the Node SSR server must also be able to `import()` the plugin bundle (same URL fetched from disk or http) — doable but adds complexity; consider CSR-only for plugin pages initially.
- **CSS:** plugins should ship precompiled CSS (Tailwind 4 build scoped to their classes, or reuse host design tokens via CSS variables); host loads it per page/plugin via a `<link>` registered in the manifest.
- **Verdict:** feasible. Import map + externals (option 1) fits Inertia + Vite 8 best for a "no Node on production" plugin story; Module Federation if independent version lifecycles are required. Either way, define a stable **host SDK surface** (React, Inertia, UI components, Wayfinder/route helper) and version it.

---

## Sources
- php.net releases JSON: https://www.php.net/releases/index.php?json
- Node schedule: https://github.com/nodejs/Release/blob/main/schedule.json ; https://nodejs.org/dist/index.json
- Laravel support policy / L13: https://laravel.com/docs/13.x/releases
- Packagist p2 API (laravel/framework, inertiajs/inertia-laravel, laravel/wayfinder, laravel/fortify, tightenco/ziggy, laravel/telescope, dedoc/scramble, phpunit/phpunit, pestphp/pest, larastan/larastan, phpstan/phpstan, laravel/pint, laravel/sanctum, spatie/*, intervention/image, laravel/scout, filament/*, livewire/livewire, league/commonmark): https://repo.packagist.org/p2/<vendor>/<name>.json
- npm registry (`npm view`) for all JS packages
- Inertia v3 upgrade guide: https://inertiajs.com/docs/v3/getting-started/upgrade-guide ; client setup: https://inertiajs.com/docs/v3/installation/client-side-setup
- TypeScript 7.0: https://devblogs.microsoft.com/typescript/announcing-typescript-7-0/
- Vite 8 migration: https://vite.dev/guide/migration
- ESLint 10: https://eslint.org/blog/2026/02/eslint-v10.0.0-released/
- Vite+ in starter kits: https://laravel-news.com/laravel-starter-kits-vite-plus ; https://github.com/laravel/maestro/pull/60
- React starter kit: https://github.com/laravel/react-starter-kit (main; composer.json, package.json, vite.config.ts, phpstan.neon, .github/workflows/tests.yml, resources/js/app.tsx)
- Filament: https://filamentphp.com/docs/5.x/upgrade-guide ; https://filamentphp.com/insights/danharrin-filament-v5-blueprint ; https://filamentphp.com/docs/5.x/plugins/panel-plugins ; https://filamentphp.com/docs/5.x/advanced/assets ; https://filamentphp.com/docs/5.x/advanced/security ; https://filamentphp.com/docs/5.x/introduction/installation
- Module Federation for Vite: https://module-federation.io/integrations/build-tool/vite.html ; https://www.npmjs.com/package/@module-federation/vite
- GitHub advisories: GHSA-jh5r-qr3c-85q8, GHSA-3q6v-r5mr-hxv8, GHSA-97jj-33gv-5xf9 (composer audit); npm audit advisories listed in §2
