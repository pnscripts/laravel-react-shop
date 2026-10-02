# Publishing a release

Status: **not set up yet.** Publishing creates public repositories and Packagist entries, so it waits for the owner's approval.

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

## Proposed publishing

1. **Repositories:** two read-only GitHub repositories, `pnscripts/pn-shop-core` and `pnscripts/pn-shop`, each holding one tree.
2. **Workflow:** a release workflow on version tags (`v1.1.0`) runs `build-dist.sh`, commits each tree to its repository and pushes the same tag.
3. **Packagist:** both repositories are registered there and update on push.

## Before tagging

- Set `PnShop::VERSION` to the release version and add the CHANGELOG entry.
- If a release shipped files in the project that the next one moves into the package, regenerate the list that `pnshop:migrate-to-package` uses. The list for 1.0 is `resources/upgrade/1.0-files.json`.
