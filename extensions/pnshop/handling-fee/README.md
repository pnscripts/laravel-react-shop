# Handling fee (reference plugin)

Adds a handling fee to orders below a subtotal. Customer groups can be exempted in Admin → Extensions → Fee exemptions.

It shows the parts of a PN Shop plugin:

| Part | File |
|---|---|
| Manifest: requirements, permission, settings | `pnshop.json` |
| Provider with install / uninstall hooks | `src/HandlingFeePlugin.php` |
| Cart totals pipeline stage | `src/ApplyHandlingFee.php` |
| Migration (run on install, rolled back on purge) | `database/migrations/` |
| Admin screen guarded by the plugin's permission | `src/Filament/Resources/Exemptions/` |
| Storefront slot (hint in the cart), no build step | `storefront/storefront.js` |
| Translations (server and storefront) | `lang/bg.json` |

Install it with `php artisan pnshop:plugin install pnshop/handling-fee` and `php artisan pnshop:plugin enable pnshop/handling-fee`, or from Admin → Extensions.
