# Publishing a release

Status: the read-only repositories [pnscripts/pn-shop-core](https://github.com/pnscripts/pn-shop-core) and [pnscripts/pn-shop](https://github.com/pnscripts/pn-shop) exist (2026-10-02). Packagist registration is done by the owner on packagist.org.

This repository is the development repository. A release produces two Composer packages:

| Package | Contents | Used by |
|---|---|---|
| `pnscripts/pn-shop-core` | `packages/pn-shop-core`, plus the prebuilt storefront in `theme/dist` | every shop, through `composer update` |
| `pnscripts/pn-shop` | the project skeleton: this repository without `packages/`, tests, docs and CI, with `composer.json` requiring `pnscripts/pn-shop-core ^<major>.<minor>` | `composer create-project` |

## Building the trees

From a clean checkout of the release commit, after `npm ci`:

```bash
scripts/release/build-dist.sh /tmp/pn-shop-release
```

The script:

1. Exports `packages/pn-shop-core` and builds the storefront into its `theme/dist`.
2. Exports the skeleton.
3. Points the skeleton's tooling configs at `vendor/pnscripts/pn-shop-core`.
4. Removes the path repository and the development-repository flag from the skeleton's `composer.json`.

## Publishing

From a clean checkout of the commit, after `npm ci` and `composer install`, with your own git credentials:

```bash
scripts/release/publish.sh            # update both repositories' main branches (Composer: dev-main)
scripts/release/publish.sh v1.1.0     # a release: also tags both repositories
```

The script:

1. Builds both trees with `build-dist.sh`.
2. Replaces each repository's contents with its tree, and adds a read-only README and the LICENSE.
3. Commits with a link to the source commit and pushes `main`.
4. With a tag, it first checks that `PnShop::VERSION` matches, then pushes the tag to both repositories.

No CI secrets are involved. Packagist picks up new tags through its GitHub integration once each repository is submitted there.

## Before tagging

- Set `PnShop::VERSION` to the release version and add the CHANGELOG entry.
- If a release shipped files in the project that the next one moves into the package, regenerate the list that `pnshop:migrate-to-package` uses. The list for 1.0 is `resources/upgrade/1.0-files.json`.
