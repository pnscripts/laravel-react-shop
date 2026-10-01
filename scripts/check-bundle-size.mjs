// Fails when a built storefront file grows past its gzip budget (run after `npm run build`).
import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { gzipSync } from 'node:zlib';

const directory = 'public/build/assets';
const budgets = { '.js': 150 * 1024, '.css': 30 * 1024 };
let failed = false;

for (const file of readdirSync(directory)) {
    const extension = file.slice(file.lastIndexOf('.'));
    const budget = budgets[extension];

    if (budget === undefined || !statSync(join(directory, file)).isFile()) {
        continue;
    }

    const size = gzipSync(readFileSync(join(directory, file))).length;

    if (size > budget) {
        failed = true;
        console.error(`${file}: ${(size / 1024).toFixed(1)} KB gzipped, budget ${budget / 1024} KB`);
    }
}

if (failed) {
    process.exit(1);
}

console.log('Bundle sizes are within budget.');
