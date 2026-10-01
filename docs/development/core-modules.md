# Core modules and the extension kernel

PN Shop's platform code lives in `core/` under the `PnShop\` namespace. `app/` remains the merchant's application space; legacy catalog and order code still sits in `app/Models` and `app/Services`, and moves into its modules during the catalog and sales redesign.

## Layout

```
core/
├── Foundation/              PnShop (version), PnShopServiceProvider, ModuleServiceProvider
│   └── Extension/           Permission, PermissionRegistry, PipelineRegistry
├── Settings/                typed, cached settings + admin page
├── Acl/                     staff accounts (admin_users), roles, permissions, commands
├── System/                  activity log
├── Catalog/                 product permissions, policy, admin resource, low-stock widget
├── Sales/                   order permissions, policy, admin resource, dashboard widgets
└── Admin/                   the Filament panel (/admin)
```

Modules are listed, in boot order, in `config/pnshop.php`. `PnShopServiceProvider` (registered in `bootstrap/providers.php`) registers the kernel singletons and then every module.

A module provider extends `PnShop\Foundation\ModuleServiceProvider`, which:

- loads `core/<Module>/database/migrations` automatically;
- registers the module's `permissions()` with the permission registry;
- calls `bootModule()` for anything else (policies, settings schemas, listeners).

Admin screens are discovered from `core/<Module>/Filament/{Resources,Pages,Widgets}`. A module needs no panel changes to add screens.

## Extension kernel

These are the stable extension points that core modules use today, and that plugins will use once the extension system lands (Phase 8).

### Permissions

```php
use PnShop\Foundation\Extension\Permission;

protected function permissions(): array
{
    return [
        new Permission('catalog.brands.manage', 'Manage brands', 'Catalog'),
    ];
}
```

- Keys are dotted lowercase: `<area>.<resource>.<action>`.
- `PermissionSynchronizer` writes new keys to the database after every `php artisan migrate` (and on `pnshop:permissions:sync`).
- A newly created permission is granted once to each built-in role whose pattern matches it (see `PnShop\Acl\DefaultRoles`). Later edits in the admin are never overwritten.
- Permissions that are no longer registered are kept, so disabling and re-enabling an extension preserves role assignments.
- Administrators pass every permission check through `Gate::before`, but **policy abilities** (`delete`, `update`, …) still run their own rules.

### Pipelines

```php
use PnShop\Foundation\Extension\PipelineRegistry;

app(PipelineRegistry::class)->stage('cart.totals', ApplyLoyaltyDiscount::class, priority: 200);
$result = app(PipelineRegistry::class)->run('cart.totals', $cart);
```

Stages work like middleware: `handle($payload, Closure $next)`. Lower priorities run first. Pipelines replace "override a core class" patterns; cart totals and price resolution (Phases 5–6) will use them.

### Settings

```php
use PnShop\Settings\{SettingDefinition, SettingsRegistry, SettingsSchema, SettingType};

app(SettingsRegistry::class)->register(new SettingsSchema(
    'plugin.acme.seo',               // namespace: store, seo, theme.<id>, plugin.<vendor>.<name>
    'Advanced SEO',                  // tab label in Admin → System → Settings
    new SettingDefinition('enabled', SettingType::Boolean, 'Enabled', default: true),
    new SettingDefinition('title_suffix', SettingType::String, 'Title suffix', rules: ['max:60']),
));

app(\PnShop\Settings\Settings::class)->get('plugin.acme.seo.enabled');      // typed, cached
app(\PnShop\Settings\Settings::class)->set('plugin.acme.seo', ['enabled' => false]); // validated
```

- Every schema gets its own tab on the settings page automatically.
- Values are stored per `(namespace, key)` in the `settings` table, so extensions cannot write into each other's or core's settings.
- Unknown keys throw, and values are validated against the definition before they are stored.

## Static analysis

- `core/` is analysed at Larastan level 7 (`phpstan-core.neon`).
- The legacy `app/` code stays at level 5 (`phpstan.neon`) until it is replaced.

CI runs both.
