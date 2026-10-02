<?php

namespace PnShop\Acl\Filament\Resources\AdminUsers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;
use PnShop\Acl\Models\AdminUser;
use Spatie\Permission\Models\Role;

class AdminUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->rule(Password::defaults())
                        ->required(fn ($livewire) => $livewire instanceof CreateRecord)
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->helperText(fn ($livewire) => $livewire instanceof CreateRecord ? null : 'Leave empty to keep the current password.'),
                    Select::make('roles')
                        // Only roles the signed-in staff member may hand out (no privilege escalation).
                        ->relationship('roles', 'name', fn ($query) => $query->where('guard_name', 'admin')->whereIn('id', self::assignableRoleIds()))
                        ->multiple()
                        ->preload()
                        ->rule(fn () => function (string $attribute, mixed $value, \Closure $fail): void {
                            if (array_diff(array_map('intval', (array) $value), self::assignableRoleIds()) !== []) {
                                $fail('You may only assign roles whose permissions you hold yourself.');
                            }
                        }),
                    Toggle::make('is_active')
                        ->label('Can sign in')
                        ->default(true)
                        ->disabled(fn (?AdminUser $record) => $record !== null && $record->is(auth('admin')->user())),
                ]),
        ]);
    }

    /**
     * @return list<int>
     */
    public static function assignableRoleIds(): array
    {
        $actor = auth('admin')->user();

        if (! $actor instanceof AdminUser) {
            return [];
        }

        return array_values(Role::query()->where('guard_name', 'admin')->with('permissions')->get()
            ->filter(fn (Role $role) => $actor->mayAssign($role))
            ->map(fn (Role $role) => (int) $role->id)
            ->all());
    }
}
