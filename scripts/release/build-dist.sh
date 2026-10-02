#!/usr/bin/env bash
# Builds the two release trees from a commit of this repository:
#
#   <out>/pn-shop-core   the pnscripts/pn-shop-core package, with the prebuilt storefront (theme/dist)
#   <out>/pn-shop        the pnscripts/pn-shop project skeleton (composer create-project)
#
# Usage: scripts/release/build-dist.sh <out-dir> [<commit>]
# Run from the root of a clean checkout of <commit>, after npm ci: the storefront is built from the working tree.
set -euo pipefail

out=${1:?Usage: scripts/release/build-dist.sh <out-dir> [<commit>]}
commit=${2:-HEAD}
root=$(pwd)

if [ -e "$out/pn-shop-core" ] || [ -e "$out/pn-shop" ]; then
    echo "$out already holds a build; use an empty folder." >&2
    exit 1
fi

mkdir -p "$out/pn-shop-core" "$out/pn-shop"
out=$(cd "$out" && pwd)

# The core package: its folder, plus the storefront built from that same commit.
git archive "$commit" packages/pn-shop-core | tar -x -C "$out/pn-shop-core" --strip-components=2
PNSHOP_CORE_DIST=1 npx vite build --logLevel warn
cp -R packages/pn-shop-core/theme/dist "$out/pn-shop-core/theme/dist"

# The skeleton: the project without the core, the repository's own tests, docs and CI.
git archive "$commit" | tar -x -C "$out/pn-shop" \
    --exclude=packages --exclude=docs --exclude=tests --exclude=.github --exclude=scripts/release \
    --exclude=AGENTS.md --exclude=CLAUDE.md --exclude=CHANGELOG.md --exclude=composer.lock \
    --exclude=phpstan-core.neon --exclude=phpunit.xml

cd "$out/pn-shop"

# Tooling configs point at the installed package instead of packages/pn-shop-core.
for file in tsconfig.json components.json .prettierignore package.json eslint.config.js phpstan.neon; do
    [ -f "$file" ] && sed -i.bak 's#packages/pn-shop-core#vendor/pnscripts/pn-shop-core#g' "$file" && rm -f "$file.bak"
done

php -r '
$file = "composer.json";
$composer = json_decode(file_get_contents($file), true);
preg_match("/VERSION = \x27(\d+)\.(\d+)/", file_get_contents($argv[1]), $version);
$composer["require"]["pnscripts/pn-shop-core"] = "^{$version[1]}.{$version[2]}";
$composer["repositories"] = array_values(array_filter($composer["repositories"] ?? [], fn ($r) => ($r["type"] ?? null) !== "path"));
if ($composer["repositories"] === []) unset($composer["repositories"]);
unset($composer["extra"]["pnshop"]);
file_put_contents($file, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
' "$root/packages/pn-shop-core/src/Foundation/PnShop.php"

echo "Built $out/pn-shop-core and $out/pn-shop from $(cd "$root" && git rev-parse --short "$commit")."
