<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copies users flagged with is_admin into admin_users (keeping their password hash),
 * gives them the administrator role, and drops the flag. Their customer accounts remain.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_admin')) {
            return;
        }

        $now = now();

        $roleId = DB::table('roles')->where(['name' => 'administrator', 'guard_name' => 'admin'])->value('id')
            ?? DB::table('roles')->insertGetId(['name' => 'administrator', 'guard_name' => 'admin', 'created_at' => $now, 'updated_at' => $now]);

        foreach (DB::table('users')->where('is_admin', true)->get(['name', 'email', 'password']) as $user) {
            if (DB::table('admin_users')->where('email', $user->email)->exists()) {
                continue;
            }

            $adminId = DB::table('admin_users')->insertGetId([
                'name' => $user->name,
                'email' => $user->email,
                'password' => $user->password,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('model_has_roles')->insert([
                'role_id' => $roleId,
                'model_type' => 'admin_user',
                'model_id' => $adminId,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
        });
    }
};
