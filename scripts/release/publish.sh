#!/usr/bin/env bash
# Publishes the release trees to their read-only repositories:
#
#   packages/pn-shop-core (+ prebuilt storefront)  ->  github.com/pnscripts/pn-shop-core
#   the project skeleton                           ->  github.com/pnscripts/pn-shop
#
# Usage: scripts/release/publish.sh [<tag>]      e.g. scripts/release/publish.sh v1.1.0
#
# Run from a clean checkout of the commit to publish, after npm ci and composer install.
# Without a tag, the repositories' main branches are updated (Composer: dev-main).
# Uses your own git credentials; nothing is stored in CI.
set -euo pipefail

tag=${1:-}
owner=${PNSHOP_PUBLISH_OWNER:-pnscripts}
root=$(pwd)

if [ -n "$tag" ] && ! [[ "$tag" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "The tag must look like v1.1.0." >&2
    exit 1
fi

if [ -n "$(git status --porcelain)" ]; then
    echo "The working tree has changes; publish from a clean checkout." >&2
    exit 1
fi

if [ -n "$tag" ]; then
    version=$(sed -n "s/.*VERSION = '\([^']*\)'.*/\1/p" packages/pn-shop-core/src/Foundation/PnShop.php)
    if [ "v$version" != "$tag" ]; then
        echo "PnShop::VERSION is $version; set it to ${tag#v} before tagging $tag." >&2
        exit 1
    fi
fi

commit=$(git rev-parse HEAD)
work=$(mktemp -d)
trap 'rm -rf "$work"' EXIT

scripts/release/build-dist.sh "$work/dist"

publish() {
    local name=$1 title=$2 about=$3
    local tree="$work/dist/$name" checkout="$work/$name"

    git clone -q "git@github.com:$owner/$name.git" "$checkout"
    git -C "$checkout" checkout -q -B main

    # Replace everything but .git with the new tree.
    find "$checkout" -mindepth 1 -maxdepth 1 ! -name .git -exec rm -rf {} +
    cp -a "$tree/." "$checkout/"
    cp "$root/LICENSE" "$checkout/LICENSE"
    cat > "$checkout/README.md" <<EOF
# $title

$about

**This repository is read-only.** It is published from [pnscripts/pn-shop-source](https://github.com/pnscripts/pn-shop-source) (\`scripts/release/publish.sh\`); send issues and pull requests there.

Documentation: <https://github.com/pnscripts/pn-shop-source/tree/main/docs>
EOF

    git -C "$checkout" add -A
    if git -C "$checkout" diff --cached --quiet; then
        echo "$name: no changes."
    else
        git -C "$checkout" commit -qm "Publish pn-shop-source@${commit:0:7}${tag:+ ($tag)}" -m "Source: https://github.com/pnscripts/pn-shop-source/commit/$commit"
    fi

    git -C "$checkout" push -q origin main
    if [ -n "$tag" ]; then
        git -C "$checkout" tag -a "$tag" -m "PN Shop ${tag#v}"
        git -C "$checkout" push -q origin "$tag"
    fi

    echo "$name: published${tag:+ $tag}."
}

publish pn-shop-core "pnscripts/pn-shop-core" "The PN Shop platform core: modules, storefront and installer, as a Composer package. Shops require it through the \`pnscripts/pn-shop\` project."
publish pn-shop "pnscripts/pn-shop" "A PN Shop project. Create a shop with \`composer create-project pnscripts/pn-shop shop\`, then run \`php artisan pnshop:install\`."
