<?php

namespace PnShop\Api\Console;

use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use PnShop\Acl\Models\AdminUser;
use PnShop\Api\StaffTokens;

class ApiTokenCommand extends Command
{
    protected $signature = 'pnshop:api-token
        {email : Email address of the staff account that owns the token}
        {--name=Integration : Name shown in the admin}
        {--ability=* : Permission key the token may use (repeat the option); omit with --all}
        {--all : The token may do everything the account may do}
        {--days=365 : Expire after this many days (default: a year)}';

    protected $description = 'Create an Admin API token for a staff account and print it once';

    public function handle(StaffTokens $tokens): int
    {
        $admin = AdminUser::query()->where('email', $this->argument('email'))->first();

        if ($admin === null || ! $admin->is_active) {
            $this->error('No active staff account with that email.');

            return self::FAILURE;
        }

        /** @var list<string> $abilities */
        $abilities = $this->option('all') ? [StaffTokens::ALL] : $this->option('ability');
        $days = $this->option('days');

        try {
            $token = $tokens->issue($admin, (string) $this->option('name'), $abilities, now()->addDays(is_numeric($days) && (int) $days > 0 ? (int) $days : 365));
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                array_map(fn (string $message) => $this->error($message), $messages);
            }

            return self::FAILURE;
        }

        $this->info('Token created. It is shown only once:');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
