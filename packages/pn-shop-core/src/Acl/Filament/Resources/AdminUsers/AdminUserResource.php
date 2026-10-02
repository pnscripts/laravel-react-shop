<?php

namespace PnShop\Acl\Filament\Resources\AdminUsers;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use PnShop\Acl\Filament\Resources\AdminUsers\Pages\CreateAdminUser;
use PnShop\Acl\Filament\Resources\AdminUsers\Pages\EditAdminUser;
use PnShop\Acl\Filament\Resources\AdminUsers\Pages\ListAdminUsers;
use PnShop\Acl\Filament\Resources\AdminUsers\Schemas\AdminUserForm;
use PnShop\Acl\Filament\Resources\AdminUsers\Tables\AdminUsersTable;
use PnShop\Acl\Models\AdminUser;
use UnitEnum;

class AdminUserResource extends Resource
{
    protected static ?string $model = AdminUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Admin users';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return AdminUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminUsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminUsers::route('/'),
            'create' => CreateAdminUser::route('/create'),
            'edit' => EditAdminUser::route('/{record}/edit'),
        ];
    }
}
