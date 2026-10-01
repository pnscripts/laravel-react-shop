<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminCommand extends Command
{
    protected $signature = 'pnshop:create-admin
        {email? : Email address of the administrator}
        {--name= : Display name}
        {--generate-password : Generate a random password and print it once}';

    protected $description = 'Create a PN Shop administrator, or promote an existing user to administrator';

    public function handle(): int
    {
        $email = $this->argument('email') ?? text('Email', required: true);
        $name = $this->option('name') ?? (User::where('email', $email)->value('name') ?? text('Name', required: true));

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

        $user = User::firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->password = $plain;
        $user->email_verified_at ??= now();
        $user->is_admin = true;
        $user->save();

        $this->info("Administrator {$email} is ready.");

        if ($generated !== null) {
            $this->line("Generated password (shown once): {$generated}");
        }

        return self::SUCCESS;
    }
}
