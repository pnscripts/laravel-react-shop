# Admin panel, staff accounts and roles

The administration panel is at **`/admin`**. Staff accounts are separate from customer accounts:

| | Customers | Staff |
|---|---|---|
| Table | `users` | `admin_users` |
| Guard | `web` | `admin` |
| Sign-in | `/login` (storefront) | `/admin/login` |
| Password reset | `/forgot-password` | `/admin/password-reset/request` |

A customer account can never open the admin, and signing in as staff does not sign you in to the storefront.

## First administrator

```bash
php artisan pnshop:create-admin you@example.com --name="Your Name"
```

The command asks for a password; `--generate-password` prints a random one once instead. Running it again for an existing staff email resets that account's password, re-activates it and makes it an administrator. This is the recovery path if you lock yourself out.

## Roles

| Role | Can |
|---|---|
| administrator | everything, including permissions added by future modules and extensions |
| manager | catalog, content, sales, customers, marketing |
| content-editor | content |
| catalog-manager | catalog |
| order-manager | sales (orders) |
| customer-manager | customers |

- Edit roles or create your own under **System → Roles**. The administrator role is not editable.
- Assign roles to staff under **System → Admin users**.
- An account switched off with *Can sign in* is refused at login.

Safety rules:

- nobody can delete their own account;
- the last active administrator cannot be deleted, demoted or deactivated;
- a role that is still assigned to someone cannot be deleted.

## Current permissions

| Permission | Meaning |
|---|---|
| `catalog.products.view` / `create` / `update` / `delete` | Products |
| `sales.orders.view` | See orders |
| `sales.orders.update` | Change order status (cancelling returns stock) |
| `system.admin_users.manage` | Staff accounts |
| `system.roles.manage` | Roles and permissions |
| `system.settings.manage` | Settings |
| `system.activity.view` | Activity log |

## Activity log

**System → Activity log** records:

- product changes (title, prices, stock, visibility, SKU, category);
- order status changes;
- staff account changes;
- settings saves.

Each entry stores who made the change and the old and new values.
