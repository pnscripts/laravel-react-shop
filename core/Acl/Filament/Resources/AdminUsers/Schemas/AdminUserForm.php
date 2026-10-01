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
                        ->relationship('roles', 'name', fn ($query) => $query->where('guard_name', 'admin'))
                        ->multiple()
                        ->preload(),
                    Toggle::make('is_active')
                        ->label('Can sign in')
                        ->default(true)
                        ->disabled(fn (?AdminUser $record) => $record !== null && $record->is(auth('admin')->user())),
                ]),
        ]);
    }
}
