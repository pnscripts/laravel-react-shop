<?php

namespace PnShop\Acl\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use PnShop\Acl\Models\AdminUser;
use PnShop\Acl\PermissionSynchronizer;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminCommand extends Command
{
    protected $signature = 'pnshop:create-admin
        {email? : Email address of the administrator}
        {--name= : Display name}
        {--generate-password : Generate a random password and print it once}';

    protected $description = 'Create a PN Shop administrator, or reset an existing staff account and make it an administrator';

    public function handle(PermissionSynchronizer $permissions): int
    {
        $email = $this->argument('email') ?? text('Email', required: true);
        $name = $this->option('name') ?? (AdminUser::query()->where('email', $email)->value('name') ?? text('Name', required: true));

        $generated = $this->option('generate-password') ? Str::password(20) : null;
        $plain = $generated ?? password('Password', required: true);

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $plain],
            ['email' => ['required', 'email', 'max:255'], 'name' => ['required', 'string', 'max:255'], 'password' => [Password::defaults()]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $permissions->sync();

        $admin = AdminUser::query()->firstOrNew(['email' => $email]);
        $admin->name = $name;
        $admin->password = $plain;
        $admin->is_active = true;
        $admin->save();
        $admin->assignRole(AdminUser::ADMINISTRATOR_ROLE);

        $this->info("Administrator {$email} is ready. Sign in at ".url('/admin'));

        if ($generated !== null) {
            $this->line("Generated password (shown once): {$generated}");
        }

        return self::SUCCESS;
    }
}
