# Updating PN Shop

An update has two parts: replace the code, then let PN Shop update the database and caches with `php artisan pnshop:update`.

```bash
php artisan pnshop:update --dry-run      # what will happen; changes nothing
composer update pnscripts/pn-shop-core   # or unpack the new release over the old files (keep .env and storage/)
php artisan pnshop:update
```

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
5. **Caches:** caches are cleared, the plugin boot file is rebuilt, the active theme is published again and the storage link is checked.
6. **Smoke checks:** the database answers, the core tables exist, and settings and currency load.
7. **Version history:** the update is recorded in the version history (Admin → System → Version and updates).

If a step fails, the command says so and the shop comes back online. Restore the database from the backup if the migration left it half-changed.

## Themes

A theme other than the default ships its own prebuilt bundle. After an update that changes the storefront, rebuild it with `npm run build:theme -- <vendor/name>` (or get the theme's new release). The update publishes it again.

## Versions

PN Shop follows semantic versioning. Breaking changes to plugin contracts come only in major versions and are announced one minor version ahead. Each plugin declares the versions it supports (`requires.pnshop`), and the update checks them.
