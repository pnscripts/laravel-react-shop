<?php

namespace PnShop\Acl\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;
use PnShop\Foundation\Extension\PermissionRegistry;
use Spatie\Permission\Models\Permission;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        $registry = app(PermissionRegistry::class);

        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(125)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->where('guard_name', 'admin')),
                Hidden::make('guard_name')->default('admin'),
            ]),
            Section::make('Permissions')->schema([
                CheckboxList::make('permissions')
                    ->hiddenLabel()
                    ->relationship(
                        'permissions',
                        'name',
                        fn ($query) => $query->where('guard_name', 'admin')->orderBy('name'),
                    )
                    ->getOptionLabelFromRecordUsing(function (Permission $permission) use ($registry): string {
                        $definition = $registry->all()[$permission->name] ?? null;

                        return $definition ? "{$definition->group}: {$definition->label}" : $permission->name;
                    })
                    ->columns(2)
                    ->bulkToggleable()
                    ->searchable(),
            ]),
        ]);
    }
}
