#!/usr/bin/env node
// npm run build:theme -- <vendor/name>: build a theme's storefront bundle into themes/<vendor>/<name>/dist.
import { spawnSync } from 'node:child_process';

const id = process.argv[2] ?? '';

if (!/^[a-z0-9-]+\/[a-z0-9-]+$/.test(id)) {
    console.error('Usage: npm run build:theme -- <vendor/name>');
    process.exit(1);
}

const result = spawnSync('npx', ['vite', 'build', '--config', 'vite.theme.config.ts'], {
    stdio: 'inherit',
    env: { ...process.env, PNSHOP_THEME: id },
});

process.exit(result.status ?? 1);
