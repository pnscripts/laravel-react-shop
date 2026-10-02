# Updating PN Shop

The core of PN Shop is the Composer package `pnscripts/pn-shop-core`. Your shop project (created from `pnscripts/pn-shop`) holds only your own code, configuration, plugins and themes. An update has two parts: get the new core with Composer, then let PN Shop update the database and caches.

```bash
composer update pnscripts/pn-shop-core --with-all-dependencies
php artisan pnshop:update --dry-run      # what will happen; changes nothing
php artisan pnshop:update
```

- **What Composer does:** it installs the new core and publishes its prebuilt storefront to `public/vendor/pnshop/build`. The project's `composer.json` allows updates within the major version (`^1.1`).
- **Deploying with a lock file:** commit `composer.lock` and run `composer install --no-dev --optimize-autoloader` on the server. `pnshop:update` refuses to run when `composer.lock` and `vendor/` disagree about the core.
- **Downgrades:** `pnshop:update` also refuses to run when the database comes from a newer core than the code. Restore the backup instead.
- **Git clones of the PN Shop repository:** `git pull --ff-only && composer install`, then `pnshop:update`. Here the core comes from `packages/pn-shop-core` in the repository and the storefront is built from source (`npm ci && npm run build`), as in 1.0. To switch to the published package, see [From 1.0 to 1.1](#from-10-to-11).

## From 1.0 to 1.1

In 1.0 the core lived inside the project (`core/`, `app/Http`, `resources/js`, the base migrations). In 1.1 it moved into the package. The database does not change.

1. **Back up** the database and the project folder.
2. **Get the 1.1 project files.**
   - From a release archive: unpack the 1.1 `pnscripts/pn-shop` archive over the old files. Keep `.env`, `storage/`, and your own plugins and themes.
   - From a git clone: `git pull --ff-only`.
3. **Re-apply your own changes** to these files, which 1.1 replaced: `bootstrap/app.php`, `bootstrap/providers.php`, `app/Models/User.php` (it now extends `PnShop\Customer\Models\User`), `routes/web.php` (now only your own routes), `config/pnshop.php` (`modules` became `extra_modules`).
4. **Install the core:** `composer update` (archive) or `composer install` (git clone).
5. **Set the old files aside:** `php artisan pnshop:migrate-to-package --dry-run`, then without `--dry-run`.
   - The files that 1.0 kept in the project and 1.1 moved into the package go to `storage/app/pnshop-migration/<date>/`. Nothing is deleted, and files that 1.0 did not ship stay where they are.
   - The command lists the files you had changed, so you can carry the changes into your own code, a plugin or a theme.
   - It also checks the wiring from step 3.
   - In a git clone it refuses, because the core is meant to stay in `packages/pn-shop-core` there. Add `--leave-repository` to switch to the published package and stop pulling from the repository. Then run `composer update pnscripts/pn-shop-core --with-all-dependencies` and the command once more, which sets `packages/pn-shop-core` aside.
6. **Finish:** `php artisan pnshop:update`.

Rebuild non-default themes afterwards (`npm run build:theme -- <vendor/name>`): their builds now take the storefront from the package.

## Checking first

`--dry-run` shows the following, without changing anything:

- the database version and the code version;
- pending migrations;
- plugins that will be updated;
- enabled plugins that do not support the new version;
- missing server requirements.

It exits with an error code when something blocks the update, so it can gate a deployment script.

## What the update does

1. **Checks:** requirements and plugin compatibility. A plugin that does not support the new version stops the update, unless you run with `--disable-incompatible` (it is disabled and can be enabled again once updated).
2. **Backup:** to `storage/app/backups/<date>-pnshop-<from>-to-<to>/`, readable only by the owner.
   - What is saved: the database (an SQLite copy, or `mysqldump` / `pg_dump`) and `.env`. With `--with-files`, `storage/app` (uploads) is saved too.
   - Skipping it: use `--no-backup` only when you made your own backup.
   - Other destinations: bind your own `PnShop\Installer\Contracts\BackupDriver`, for example in a plugin, to store backups off-site.
3. **Maintenance mode:** turned on while the update runs, and always turned off at the end, even when the update fails.
4. **Database:** core and plugin migrations run, then newer plugin versions found in `extensions/` are updated.
5. **Caches:** caches are cleared, the plugin boot file is rebuilt, the core's storefront and the active theme are published again, and the storage link is checked.
6. **Smoke checks:** the database answers, the core tables exist, and settings and currency load.
7. **Version history:** the update is recorded in the version history (Admin → System → Version and updates).

If a step fails, the command says so and the shop comes back online. Restore the database from the backup if the migration left it half-changed.

## Themes

The default storefront comes prebuilt with the core and needs no Node on the server. If you build the storefront yourself (`npm run build`, which writes `public/build`), that build is used instead of the core's, so rebuild it after each core update or delete `public/build`.

A theme other than the default ships its own prebuilt bundle. After an update that changes the storefront, rebuild it with `npm run build:theme -- <vendor/name>` (or get the theme's new release). The update publishes it again.

## Versions

PN Shop follows semantic versioning. Breaking changes to plugin contracts come only in major versions and are announced one minor version ahead. Each plugin declares the versions it supports (`requires.pnshop`), and the update checks them.
