<?php

namespace PnShop\Acl\Filament\Resources\AdminUsers\Pages;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use PnShop\Acl\Filament\Resources\AdminUsers\AdminUserResource;
use PnShop\Acl\Models\AdminUser;
use PnShop\Acl\Policies\AdminUserPolicy;
use Spatie\Permission\Models\Role;

/**
 * @property AdminUser $record
 */
class EditAdminUser extends EditRecord
{
    protected static string $resource = AdminUserResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /**
     * Refuse to demote or deactivate the last active administrator.
     */
    protected function beforeSave(): void
    {
        if (! $this->record->isAdministrator() || ! $this->record->is_active) {
            return;
        }

        $administratorRoleId = Role::findByName(AdminUser::ADMINISTRATOR_ROLE, 'admin')->getKey();
        $keepsRole = in_array($administratorRoleId, array_map('intval', (array) ($this->data['roles'] ?? [])), true);
        $staysActive = (bool) ($this->data['is_active'] ?? true);

        if (($keepsRole && $staysActive) || AdminUserPolicy::administratorCount() > 1) {
            return;
        }

        Notification::make()
            ->danger()
            ->title('At least one active administrator is required.')
            ->send();

        $this->halt();
    }
}
